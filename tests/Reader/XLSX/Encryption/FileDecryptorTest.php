<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX\Encryption;

use OpenSpout\Common\Exception\IOException;
use OpenSpout\Reader\Exception\InvalidPasswordException;
use OpenSpout\Reader\Exception\PasswordRequiredException;
use OpenSpout\Reader\Exception\ReaderException;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class FileDecryptorTest extends TestCase
{
    private string $folder;

    protected function setUp(): void
    {
        $this->folder = (new TestUsingResource())->getTempFolderPath();
    }

    /**
     * @param 'agile'|'standard' $encryption
     */
    #[DataProvider('provideDecryptShouldGiveOriginalFileCases')]
    public function testDecryptShouldGiveOriginalFile(string $encryption, ?string $password, string $encryptionPassword): void
    {
        $originalFilePath = TestUsingResource::getResourcePath('one_sheet_with_shared_strings.xlsx');
        $decryptedFilePath = $this->folder.\DIRECTORY_SEPARATOR.'decrypted.xlsx';

        (new FileDecryptor())->decrypt($this->encrypt($originalFilePath, $encryption, $encryptionPassword), $password, $decryptedFilePath);

        self::assertFileEquals($originalFilePath, $decryptedFilePath);
        self::assertSame([$decryptedFilePath], glob($this->folder.\DIRECTORY_SEPARATOR.'*'), 'Only the decrypted file should be created');
    }

    public static function provideDecryptShouldGiveOriginalFileCases(): iterable
    {
        return [
            'agile' => ['agile', 'pässwörd', 'pässwörd'],
            'standard' => ['standard', 'pässwörd', 'pässwörd'],
            'default password of Excel' => ['agile', null, 'VelvetSweatshop'],
        ];
    }

    public function testIsPasswordProtected(): void
    {
        $decryptor = new FileDecryptor();
        $resourcePath = TestUsingResource::getResourcePath('one_sheet_with_shared_strings.xlsx');

        self::assertTrue($decryptor->isPasswordProtected(TestUsingResource::getResourcePath('encrypted_standard_libreoffice.xlsx')));
        self::assertTrue($decryptor->isPasswordProtected($this->encrypt($resourcePath, 'agile', 'secret')));
        self::assertTrue($decryptor->isPasswordProtected($this->encrypt($resourcePath, 'agile', 'VelvetSweatshop')));
        self::assertFalse($decryptor->isPasswordProtected($resourcePath));
    }

    #[DataProvider('provideDecryptShouldRejectFileThatIsNotPasswordProtectedCases')]
    #[DataProvider('provideIsPasswordProtectedShouldBeFalseForOtherFilesCases')]
    public function testIsPasswordProtectedShouldBeFalseForOtherFiles(string $content): void
    {
        self::assertFalse((new FileDecryptor())->isPasswordProtected($this->write($content)));
    }

    public static function provideIsPasswordProtectedShouldBeFalseForOtherFilesCases(): iterable
    {
        $file = (new CompoundFileBuilder())->addEntry('EncryptionInfo', 'info')->addEntry('EncryptedPackage', 'package')->build();

        return [
            'text' => ['Not a spreadsheet'],
            'empty' => [''],
            'only the encryption information' => [(new CompoundFileBuilder())->addEntry('EncryptionInfo', 'info')->build()],
            'only the encrypted package' => [(new CompoundFileBuilder())->addEntry('EncryptedPackage', 'package')->build()],
            'truncated compound file' => [substr($file, 0, 100)],
            'compound file with a broken directory' => [substr_replace($file, pack('V', 0xFFFFFFFF), 0x30, 4)],
        ];
    }

    public function testIsPasswordProtectedShouldRejectMissingFile(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Could not open /path/to/missing.xlsx for reading.');

        (new FileDecryptor())->isPasswordProtected('/path/to/missing.xlsx');
    }

    public function testDecryptFileEncryptedByLibreOffice(): void
    {
        $decryptedFilePath = $this->folder.\DIRECTORY_SEPARATOR.'decrypted.xlsx';

        (new FileDecryptor())->decrypt(TestUsingResource::getResourcePath('encrypted_standard_libreoffice.xlsx'), 'password', $decryptedFilePath);

        $reader = new Reader();
        $reader->open($decryptedFilePath);
        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
        }
        $reader->close();
        self::assertSame(['Header 1', 'Header 2', 'Header 3'], $rows[0]);
        self::assertCount(3, $rows);
    }

    public function testDecryptShouldOverwriteExistingFile(): void
    {
        $originalFilePath = TestUsingResource::getResourcePath('one_sheet_with_shared_strings.xlsx');
        $decryptedFilePath = $this->folder.\DIRECTORY_SEPARATOR.'decrypted.xlsx';
        file_put_contents($decryptedFilePath, 'previous content');

        (new FileDecryptor())->decrypt($this->encrypt($originalFilePath, 'agile', 'secret'), 'secret', $decryptedFilePath);

        self::assertFileEquals($originalFilePath, $decryptedFilePath);
    }

    public function testDecryptShouldRejectWrongPassword(): void
    {
        $encryptedFilePath = $this->encrypt(TestUsingResource::getResourcePath('one_sheet_with_shared_strings.xlsx'), 'agile', 'secret');

        try {
            (new FileDecryptor())->decrypt($encryptedFilePath, 'Secret', $this->folder.\DIRECTORY_SEPARATOR.'decrypted.xlsx');
            self::fail('The file should not have been decrypted');
        } catch (InvalidPasswordException) {
            self::assertSame([], glob($this->folder.\DIRECTORY_SEPARATOR.'*'), 'Nothing should be created');
        }
    }

    public function testDecryptShouldNotTouchExistingFileWhenItFails(): void
    {
        $package = (string) file_get_contents(TestUsingResource::getResourcePath('one_sheet_with_shared_strings.xlsx'));
        [$encryptionInfo, $encryptedPackage] = TestEncryptor::agile($package, 'secret');
        $container = static fn (string $encryptedPackage): string => (new CompoundFileBuilder())
            ->addEntry('EncryptionInfo', $encryptionInfo)
            ->addEntry('EncryptedPackage', $encryptedPackage)
            ->build()
        ;
        $decryptedFilePath = $this->folder.\DIRECTORY_SEPARATOR.'decrypted.xlsx';
        file_put_contents($decryptedFilePath, 'previous content');

        $failures = [
            'wrong password' => [$this->write($container($encryptedPackage)), 'Secret'],
            'altered package' => [$this->write($container(substr_replace($encryptedPackage, 'X', -1, 1))), 'secret'],
        ];
        foreach ($failures as $name => [$filePath, $password]) {
            try {
                (new FileDecryptor())->decrypt($filePath, $password, $decryptedFilePath);
                self::fail("The file should not have been decrypted: {$name}");
            } catch (ReaderException) {
                self::assertSame('previous content', file_get_contents($decryptedFilePath), $name);
                self::assertSame([$decryptedFilePath], glob($this->folder.\DIRECTORY_SEPARATOR.'*'), $name);
            }
        }
    }

    public function testDecryptShouldFailWhenDecryptedFileCannotBeCreated(): void
    {
        $encryptedFilePath = $this->encrypt(TestUsingResource::getResourcePath('one_sheet_with_shared_strings.xlsx'), 'agile', 'secret');
        // A folder cannot be replaced by a file
        $decryptedFilePath = $this->folder.\DIRECTORY_SEPARATOR.'folder';
        mkdir($decryptedFilePath);

        try {
            (new FileDecryptor())->decrypt($encryptedFilePath, 'secret', $decryptedFilePath);
            self::fail('The file should not have been decrypted');
        } catch (IOException $exception) {
            self::assertSame("Could not create {$decryptedFilePath}.", $exception->getMessage());
            self::assertSame([$decryptedFilePath], glob($this->folder.\DIRECTORY_SEPARATOR.'*'), 'Nothing should be left behind');
        }
    }

    public function testDecryptShouldRequirePassword(): void
    {
        $encryptedFilePath = $this->encrypt(TestUsingResource::getResourcePath('one_sheet_with_shared_strings.xlsx'), 'agile', 'secret');

        $this->expectException(PasswordRequiredException::class);

        (new FileDecryptor())->decrypt($encryptedFilePath, null, $this->folder.\DIRECTORY_SEPARATOR.'decrypted.xlsx');
    }

    #[DataProvider('provideDecryptShouldRejectFileThatIsNotPasswordProtectedCases')]
    public function testDecryptShouldRejectFileThatIsNotPasswordProtected(string $content): void
    {
        $filePath = $this->write($content);

        $this->expectException(IOException::class);
        $this->expectExceptionMessage("{$filePath} is not a password-protected file.");

        (new FileDecryptor())->decrypt($filePath, 'secret', $this->folder.\DIRECTORY_SEPARATOR.'decrypted.xlsx');
    }

    public static function provideDecryptShouldRejectFileThatIsNotPasswordProtectedCases(): iterable
    {
        return [
            'spreadsheet' => [(string) file_get_contents(TestUsingResource::getResourcePath('one_sheet_with_shared_strings.xlsx'))],
            'compound file' => [(new CompoundFileBuilder())->addEntry('Workbook', 'legacy file')->build()],
        ];
    }

    public function testDecryptShouldRejectMissingFile(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Could not open /path/to/missing.xlsx for reading.');

        (new FileDecryptor())->decrypt('/path/to/missing.xlsx', 'secret', $this->folder.\DIRECTORY_SEPARATOR.'decrypted.xlsx');
    }

    public function testDecryptShouldRejectFolder(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage("Could not open {$this->folder} for reading.");

        (new FileDecryptor())->decrypt($this->folder, 'secret', $this->folder.\DIRECTORY_SEPARATOR.'decrypted.xlsx');
    }

    public function testDecryptShouldRejectUnreadableFile(): void
    {
        // Permissions cannot be changed on Windows, where this test gets skipped
        (new TestUsingResource())->getGeneratedUnwritableResourcePath('unused');
        $encryptedFilePath = $this->encrypt(TestUsingResource::getResourcePath('one_sheet_with_shared_strings.xlsx'), 'agile', 'secret');
        chmod($encryptedFilePath, 0o200);

        try {
            $this->expectException(IOException::class);
            $this->expectExceptionMessage("Could not open {$encryptedFilePath} for reading.");

            (new FileDecryptor())->decrypt($encryptedFilePath, 'secret', $this->folder.\DIRECTORY_SEPARATOR.'decrypted.xlsx');
        } finally {
            chmod($encryptedFilePath, 0o600);
        }
    }

    public function testDecryptShouldRejectMissingDestinationFolder(): void
    {
        $encryptedFilePath = $this->encrypt(TestUsingResource::getResourcePath('one_sheet_with_shared_strings.xlsx'), 'agile', 'secret');
        $folder = $this->folder.\DIRECTORY_SEPARATOR.'missing';

        $this->expectException(IOException::class);
        $this->expectExceptionMessage("Could not create a file in {$folder}.");

        (new FileDecryptor())->decrypt($encryptedFilePath, 'secret', $folder.\DIRECTORY_SEPARATOR.'decrypted.xlsx');
    }

    public function testDecryptShouldRejectDestinationFolderThatIsFile(): void
    {
        $encryptedFilePath = $this->encrypt(TestUsingResource::getResourcePath('one_sheet_with_shared_strings.xlsx'), 'agile', 'secret');

        $this->expectException(IOException::class);
        $this->expectExceptionMessage("Could not create a file in {$encryptedFilePath}.");

        (new FileDecryptor())->decrypt($encryptedFilePath, 'secret', $encryptedFilePath.\DIRECTORY_SEPARATOR.'decrypted.xlsx');
    }

    public function testDecryptShouldRejectUnwritableDestinationFolder(): void
    {
        $encryptedFilePath = $this->encrypt(TestUsingResource::getResourcePath('one_sheet_with_shared_strings.xlsx'), 'agile', 'secret');
        $decryptedFilePath = (new TestUsingResource())->getGeneratedUnwritableResourcePath('decrypted.xlsx');

        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Could not create a file in '.\dirname($decryptedFilePath).'.');

        (new FileDecryptor())->decrypt($encryptedFilePath, 'secret', $decryptedFilePath);
    }

    /**
     * @param 'agile'|'standard' $encryption
     */
    private function encrypt(string $filePath, string $encryption, string $password): string
    {
        $package = file_get_contents($filePath);
        self::assertIsString($package);
        [$encryptionInfo, $encryptedPackage] = match ($encryption) {
            'agile' => TestEncryptor::agile($package, $password),
            'standard' => TestEncryptor::standard($package, $password),
        };

        return $this->write(
            (new CompoundFileBuilder())->addEntry('EncryptionInfo', $encryptionInfo)->addEntry('EncryptedPackage', $encryptedPackage)->build()
        );
    }

    private function write(string $content): string
    {
        $path = (new TestUsingResource())->getGeneratedResourcePath(uniqid('to_decrypt_').'.xlsx');
        file_put_contents($path, $content);

        return $path;
    }
}
