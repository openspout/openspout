<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX;

use OpenSpout\Common\Exception\IOException;
use OpenSpout\Reader\Exception\InvalidPasswordException;
use OpenSpout\Reader\Exception\NoSheetsFoundException;
use OpenSpout\Reader\Exception\PasswordRequiredException;
use OpenSpout\Reader\Exception\UnsupportedEncryptionException;
use OpenSpout\Reader\XLSX\Encryption\CompoundFileBuilder;
use OpenSpout\Reader\XLSX\Encryption\TestEncryptor;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\CachingStrategyFactory;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\MemoryLimit;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionHelper;
use ZipArchive;

/**
 * @internal
 */
final class EncryptedReaderTest extends TestCase
{
    /** @var non-empty-string */
    private string $tempFolder;

    protected function setUp(): void
    {
        $this->tempFolder = (new TestUsingResource())->getTempFolderPath();
    }

    public function testReadFileEncryptedByLibreOffice(): void
    {
        $expectedRows = [
            ['Header 1', 'Header 2', 'Header 3'],
            ['Row 1 Value 1', 'Row 1 Value 2', 'Row 1 Value 3'],
            ['Row 2 Value 1', 'Row 2 Value 2', 'Row 2 Value 3'],
        ];

        self::assertSame($expectedRows, $this->readAllRows(TestUsingResource::getResourcePath('encrypted_standard_libreoffice.xlsx'), 'password'));
    }

    public function testReadFileEncryptedWithNonAsciiPassword(): void
    {
        $expectedRows = $this->readAllRows(TestUsingResource::getResourcePath('sheet_with_lots_of_shared_strings.xlsx'), null);
        self::assertCount(2001, $expectedRows);

        self::assertSame($expectedRows, $this->readAllRows(
            TestUsingResource::getResourcePath('encrypted_standard_libreoffice_unicode_password.xlsx'),
            'pässwörd €😀'
        ));
    }

    /**
     * @param 'agile'|'standard' $encryption
     */
    #[DataProvider('provideEncryptionCases')]
    public function testReadEncryptedFile(string $encryption): void
    {
        $resourcePath = TestUsingResource::getResourcePath('two_sheets_with_shared_strings.xlsx');
        $expectedRows = $this->readAllRows($resourcePath, null);
        self::assertCount(10, $expectedRows);

        self::assertSame($expectedRows, $this->readAllRows($this->encrypt($resourcePath, $encryption, 'secret'), 'secret'));
    }

    /**
     * @param 'agile'|'standard' $encryption
     */
    #[DataProvider('provideEncryptionCases')]
    public function testReadEncryptedFileWithoutUserPassword(string $encryption): void
    {
        $resourcePath = TestUsingResource::getResourcePath('one_sheet_with_inline_strings.xlsx');
        $expectedRows = $this->readAllRows($resourcePath, null);
        self::assertCount(5, $expectedRows);

        self::assertSame($expectedRows, $this->readAllRows($this->encrypt($resourcePath, $encryption, 'VelvetSweatshop'), null));
    }

    public static function provideEncryptionCases(): iterable
    {
        return [['agile'], ['standard']];
    }

    public function testPasswordShouldBeIgnoredForFileThatIsNotEncrypted(): void
    {
        $resourcePath = TestUsingResource::getResourcePath('one_sheet_with_shared_strings.xlsx');

        self::assertSame($this->readAllRows($resourcePath, null), $this->readAllRows($resourcePath, 'secret'));
    }

    public function testDecryptedFileShouldOnlyExistWhileReading(): void
    {
        $reader = new Reader(new Options(tempFolder: $this->tempFolder, password: 'password'));
        self::assertSame([], $this->decryptedFiles());

        $reader->open(TestUsingResource::getResourcePath('encrypted_standard_libreoffice.xlsx'));
        self::assertCount(1, $this->decryptedFiles());

        $reader->close();
        self::assertSame([], $this->decryptedFiles());

        // Closing already took care of the decrypted file
        unset($reader);
    }

    public function testCloseShouldReleaseEverything(): void
    {
        $reader = new Reader(
            new Options(tempFolder: $this->tempFolder, password: 'password'),
            new CachingStrategyFactory(new MemoryLimit('1b'))
        );
        $reader->open(TestUsingResource::getResourcePath('encrypted_standard_libreoffice.xlsx'));
        $zip = ReflectionHelper::getValueOnObject($reader, 'zip');
        self::assertInstanceOf(ZipArchive::class, $zip);
        self::assertSame(10, $zip->numFiles);
        // The decrypted file, and the folder of the shared strings
        self::assertCount(2, (array) glob($this->tempFolder.\DIRECTORY_SEPARATOR.'*'));

        $reader->close();

        self::assertSame([], glob($this->tempFolder.\DIRECTORY_SEPARATOR.'*'));
        self::assertSame(0, $zip->numFiles);
    }

    public function testCloseShouldNotFailWhenDecryptedFileIsAlreadyDeleted(): void
    {
        $reader = new Reader(new Options(tempFolder: $this->tempFolder, password: 'password'));
        $reader->open(TestUsingResource::getResourcePath('encrypted_standard_libreoffice.xlsx'));
        $decryptedFiles = $this->decryptedFiles();
        self::assertCount(1, $decryptedFiles);
        unlink($decryptedFiles[0]);

        $reader->close();
        unset($reader);

        self::assertSame([], $this->decryptedFiles());
    }

    public function testDecryptedFileShouldBeDeletedLaterWhenItCannotBeDeletedOnClose(): void
    {
        // Permissions cannot be changed on Windows, where this test gets skipped
        (new TestUsingResource())->getGeneratedUnwritableResourcePath('unused');
        $reader = new Reader(new Options(tempFolder: $this->tempFolder, password: 'password'));
        $reader->open(TestUsingResource::getResourcePath('encrypted_standard_libreoffice.xlsx'));

        // Files cannot be deleted from a folder that is read-only
        chmod($this->tempFolder, 0o500);

        try {
            @$reader->close();
            self::assertCount(1, $this->decryptedFiles());
        } finally {
            chmod($this->tempFolder, 0o700);
        }

        unset($reader);
        self::assertSame([], $this->decryptedFiles());
    }

    public function testDecryptedFileShouldBeDeletedWhenReaderIsNotClosed(): void
    {
        $reader = new Reader(new Options(tempFolder: $this->tempFolder, password: 'password'));
        $reader->open(TestUsingResource::getResourcePath('encrypted_standard_libreoffice.xlsx'));
        self::assertCount(1, $this->decryptedFiles());

        unset($reader);
        self::assertSame([], $this->decryptedFiles());
    }

    public function testWrongPassword(): void
    {
        $exception = $this->openFailure(TestUsingResource::getResourcePath('encrypted_standard_libreoffice.xlsx'), 'Password');

        self::assertInstanceOf(InvalidPasswordException::class, $exception->getPrevious());
    }

    public function testMissingPassword(): void
    {
        $exception = $this->openFailure(TestUsingResource::getResourcePath('encrypted_standard_libreoffice.xlsx'), null);

        self::assertInstanceOf(PasswordRequiredException::class, $exception->getPrevious());
    }

    public function testUnsupportedEncryption(): void
    {
        [$encryptionInfo, $encryptedPackage] = TestEncryptor::standard('package', 'secret', 0x6801);
        $filePath = $this->write($this->container($encryptionInfo, $encryptedPackage));

        self::assertInstanceOf(UnsupportedEncryptionException::class, $this->openFailure($filePath, 'secret')->getPrevious());
    }

    public function testEncryptedFileThatIsNotSpreadsheet(): void
    {
        [$encryptionInfo, $encryptedPackage] = TestEncryptor::agile('not a ZIP file', 'secret');
        $filePath = $this->write($this->container($encryptionInfo, $encryptedPackage));

        $exception = $this->openFailure($filePath, 'secret');

        self::assertSame("Could not open {$filePath} for reading: Not a zip archive.", $exception->getMessage());
    }

    public function testEncryptedFileWithoutSheet(): void
    {
        $filePath = $this->encrypt(TestUsingResource::getResourcePath('file_with_no_sheets_in_workbook_xml.xlsx'), 'agile', 'secret');

        self::assertInstanceOf(NoSheetsFoundException::class, $this->openFailure($filePath, 'secret')->getPrevious());
    }

    public function testCompoundFileThatIsNotEncrypted(): void
    {
        $filePath = $this->write((new CompoundFileBuilder())->addEntry('Workbook', 'legacy file')->build());

        $exception = $this->openFailure($filePath, 'secret');

        self::assertSame("Could not open {$filePath} for reading: Not a zip archive.", $exception->getMessage());
    }

    private function openFailure(string $filePath, ?string $password): IOException
    {
        $reader = new Reader(new Options(tempFolder: $this->tempFolder, password: $password));

        try {
            $reader->open($filePath);
        } catch (IOException $exception) {
            self::assertSame([], $this->decryptedFiles(), 'No decrypted file should be left behind');

            return $exception;
        }

        self::fail('The file should not have been opened');
    }

    /**
     * @return list<array<mixed>>
     */
    private function readAllRows(string $filePath, ?string $password): array
    {
        $allRows = [];
        $reader = new Reader(new Options(tempFolder: $this->tempFolder, password: $password));
        $reader->open($filePath);

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $allRows[] = $row->toArray();
            }
        }

        $reader->close();
        self::assertSame([], $this->decryptedFiles());

        return $allRows;
    }

    /**
     * @return list<string>
     *
     * @phpstan-impure
     */
    private function decryptedFiles(): array
    {
        $files = glob($this->tempFolder.\DIRECTORY_SEPARATOR.'openspout_xlsx_*');
        self::assertIsArray($files);

        return $files;
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

        return $this->write($this->container($encryptionInfo, $encryptedPackage));
    }

    private function container(string $encryptionInfo, string $encryptedPackage): string
    {
        return (new CompoundFileBuilder())
            ->addEntry('EncryptionInfo', $encryptionInfo)
            ->addEntry('EncryptedPackage', $encryptedPackage)
            ->build()
        ;
    }

    private function write(string $content): string
    {
        $path = (new TestUsingResource())->getGeneratedResourcePath(uniqid('encrypted_').'.xlsx');
        file_put_contents($path, $content);

        return $path;
    }
}
