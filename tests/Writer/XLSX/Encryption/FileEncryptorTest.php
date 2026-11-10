<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Encryption;

use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Reader\Exception\InvalidPasswordException;
use OpenSpout\Reader\XLSX\Encryption\FileDecryptor;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class FileEncryptorTest extends TestCase
{
    private const RESOURCE = 'one_sheet_with_shared_strings.xlsx';

    /** @var non-empty-string */
    private string $folder;

    protected function setUp(): void
    {
        $this->folder = (new TestUsingResource())->getTempFolderPath();
    }

    public function testEncryptedFileShouldBeOriginalFileOnceDecrypted(): void
    {
        $originalFilePath = TestUsingResource::getResourcePath(self::RESOURCE);
        $encryptedFilePath = $this->folder.\DIRECTORY_SEPARATOR.'encrypted.xlsx';
        $decryptedFilePath = $this->folder.\DIRECTORY_SEPARATOR.'decrypted.xlsx';

        (new FileEncryptor())->encrypt($originalFilePath, 'pässwörd', $encryptedFilePath);

        self::assertSame([$encryptedFilePath], glob($this->folder.\DIRECTORY_SEPARATOR.'*'), 'Only the encrypted file should be created');
        self::assertSame("\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1", file_get_contents($encryptedFilePath, length: 8));

        (new FileDecryptor())->decrypt($encryptedFilePath, 'pässwörd', $decryptedFilePath);
        self::assertFileEquals($originalFilePath, $decryptedFilePath);
    }

    public function testEncryptedFileShouldBeReadableWithPassword(): void
    {
        $encryptedFilePath = $this->folder.\DIRECTORY_SEPARATOR.'encrypted.xlsx';

        (new FileEncryptor())->encrypt(TestUsingResource::getResourcePath(self::RESOURCE), 'secret', $encryptedFilePath);

        $reader = new Reader(new Options(password: 'secret'));
        $reader->open($encryptedFilePath);
        $numRows = 0;
        foreach ($reader->getSheetIterator() as $sheet) {
            $numRows += iterator_count($sheet->getRowIterator());
        }
        $reader->close();
        self::assertSame(5, $numRows);

        $this->expectException(InvalidPasswordException::class);

        (new FileDecryptor())->decrypt($encryptedFilePath, 'Secret', $this->folder.\DIRECTORY_SEPARATOR.'decrypted.xlsx');
    }

    public function testEncryptShouldOverwriteExistingFile(): void
    {
        $originalFilePath = TestUsingResource::getResourcePath(self::RESOURCE);
        $filePath = $this->folder.\DIRECTORY_SEPARATOR.'file.xlsx';
        copy($originalFilePath, $filePath);

        // The file is replaced by its password-protected version
        (new FileEncryptor())->encrypt($filePath, 'secret', $filePath);

        self::assertSame([$filePath], glob($this->folder.\DIRECTORY_SEPARATOR.'*'));
        (new FileDecryptor())->decrypt($filePath, 'secret', $filePath);
        self::assertFileEquals($originalFilePath, $filePath);
    }

    #[DataProvider('provideEncryptShouldRejectInvalidPasswordCases')]
    public function testEncryptShouldRejectInvalidPassword(string $password): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The password must be a UTF-8 string of 1 to 255 characters.');

        (new FileEncryptor())->encrypt(TestUsingResource::getResourcePath(self::RESOURCE), $password, $this->folder.\DIRECTORY_SEPARATOR.'encrypted.xlsx');
    }

    public static function provideEncryptShouldRejectInvalidPasswordCases(): iterable
    {
        return [
            'empty' => [''],
            'not UTF-8' => ["secr\xE9t"],
            'too long' => [str_repeat('é', 256)],
        ];
    }

    public function testEncryptShouldAcceptLongestPassword(): void
    {
        $encryptedFilePath = $this->folder.\DIRECTORY_SEPARATOR.'encrypted.xlsx';

        (new FileEncryptor())->encrypt(TestUsingResource::getResourcePath(self::RESOURCE), str_repeat('é', 255), $encryptedFilePath);

        self::assertFileExists($encryptedFilePath);
    }

    #[DataProvider('provideEncryptShouldRejectFileThatCannotBeProtectedCases')]
    public function testEncryptShouldRejectFileThatCannotBeProtected(string $content): void
    {
        $filePath = $this->write($content);

        try {
            (new FileEncryptor())->encrypt($filePath, 'secret', $this->folder.\DIRECTORY_SEPARATOR.'encrypted.xlsx');
            self::fail('The file should not have been encrypted');
        } catch (IOException $exception) {
            self::assertSame("{$filePath} is not an XLSX file that can be password-protected.", $exception->getMessage());
            self::assertSame([], glob($this->folder.\DIRECTORY_SEPARATOR.'*'), 'Nothing should be created');
        }
    }

    public static function provideEncryptShouldRejectFileThatCannotBeProtectedCases(): iterable
    {
        return [
            'already protected' => [(string) file_get_contents(TestUsingResource::getResourcePath('encrypted_standard_libreoffice.xlsx'))],
            'text' => ['Not a spreadsheet'],
            'almost a ZIP file' => ["PK\x03"],
            'empty' => [''],
        ];
    }

    public function testEncryptShouldRejectFileThatIsTooBig(): void
    {
        // A file made of a ZIP signature followed by 2 GiB of nothing, that does not use any disk space
        $filePath = (new TestUsingResource())->getGeneratedResourcePath(uniqid('too_big_').'.xlsx');
        $file = fopen($filePath, 'w');
        self::assertIsResource($file);
        fwrite($file, "PK\x03\x04");
        ftruncate($file, 2 ** 31);
        fclose($file);

        try {
            (new FileEncryptor())->encrypt($filePath, 'secret', $this->folder.\DIRECTORY_SEPARATOR.'encrypted.xlsx');
            self::fail('The file should not have been encrypted');
        } catch (IOException $exception) {
            self::assertSame('The spreadsheet is too big to be password-protected.', $exception->getMessage());
            self::assertSame([], glob($this->folder.\DIRECTORY_SEPARATOR.'*'), 'Nothing should be left behind');
        } finally {
            unlink($filePath);
        }
    }

    public function testEncryptShouldRejectMissingFile(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Could not open /path/to/missing.xlsx for reading.');

        (new FileEncryptor())->encrypt('/path/to/missing.xlsx', 'secret', $this->folder.\DIRECTORY_SEPARATOR.'encrypted.xlsx');
    }

    public function testEncryptShouldRejectFolder(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage("Could not open {$this->folder} for reading.");

        (new FileEncryptor())->encrypt($this->folder, 'secret', $this->folder.\DIRECTORY_SEPARATOR.'encrypted.xlsx');
    }

    public function testEncryptShouldRejectUnreadableFile(): void
    {
        // Permissions cannot be changed on Windows, where this test gets skipped
        (new TestUsingResource())->getGeneratedUnwritableResourcePath('unused');
        $filePath = $this->write((string) file_get_contents(TestUsingResource::getResourcePath(self::RESOURCE)));
        chmod($filePath, 0o200);

        try {
            $this->expectException(IOException::class);
            $this->expectExceptionMessage("Could not open {$filePath} for reading.");

            (new FileEncryptor())->encrypt($filePath, 'secret', $this->folder.\DIRECTORY_SEPARATOR.'encrypted.xlsx');
        } finally {
            chmod($filePath, 0o600);
        }
    }

    public function testEncryptShouldRejectMissingDestinationFolder(): void
    {
        $folder = $this->folder.\DIRECTORY_SEPARATOR.'missing';

        $this->expectException(IOException::class);
        $this->expectExceptionMessage("Could not create a file in {$folder}.");

        (new FileEncryptor())->encrypt(TestUsingResource::getResourcePath(self::RESOURCE), 'secret', $folder.\DIRECTORY_SEPARATOR.'encrypted.xlsx');
    }

    public function testEncryptShouldRejectDestinationFolderThatIsFile(): void
    {
        $filePath = TestUsingResource::getResourcePath(self::RESOURCE);

        $this->expectException(IOException::class);
        $this->expectExceptionMessage("Could not create a file in {$filePath}.");

        (new FileEncryptor())->encrypt($filePath, 'secret', $filePath.\DIRECTORY_SEPARATOR.'encrypted.xlsx');
    }

    public function testEncryptShouldRejectUnwritableDestinationFolder(): void
    {
        $encryptedFilePath = (new TestUsingResource())->getGeneratedUnwritableResourcePath('encrypted.xlsx');

        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Could not create a file in '.\dirname($encryptedFilePath).'.');

        (new FileEncryptor())->encrypt(TestUsingResource::getResourcePath(self::RESOURCE), 'secret', $encryptedFilePath);
    }

    private function write(string $content): string
    {
        $path = (new TestUsingResource())->getGeneratedResourcePath(uniqid('to_encrypt_').'.xlsx');
        file_put_contents($path, $content);

        return $path;
    }
}
