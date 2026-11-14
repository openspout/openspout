<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Reader\Exception\InvalidPasswordException;
use OpenSpout\Reader\Exception\PasswordRequiredException;
use OpenSpout\Reader\XLSX\Options as ReaderOptions;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class EncryptedWriterTest extends TestCase
{
    private const ROWS = [
        ['Name', 'Value'],
        ['First', 1],
        ['Second', 2.5],
    ];

    /** @var non-empty-string */
    private string $tempFolder;

    protected function setUp(): void
    {
        $this->tempFolder = (new TestUsingResource())->getTempFolderPath();
    }

    #[DataProvider('provideWritePasswordProtectedFileCases')]
    public function testWritePasswordProtectedFile(string $password, bool $shouldUseInlineStrings): void
    {
        $filePath = $this->writeFile($password, $shouldUseInlineStrings);

        self::assertSame("\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1", file_get_contents($filePath, length: 8));
        self::assertSame([self::ROWS, [['Other sheet']]], $this->readFile($filePath, $password));
        self::assertSame([], glob($this->tempFolder.\DIRECTORY_SEPARATOR.'*'), 'Nothing should be left in the temp folder');
    }

    public static function provideWritePasswordProtectedFileCases(): iterable
    {
        return [
            'inline strings' => ['secret', true],
            'shared strings' => ['secret', false],
            'non ASCII password' => ['pässwörd €😀', true],
            'longest password' => [str_repeat('é', 255), true],
            'password with line feeds' => ["\n\n", true],
            'password with spaces' => [' ', true],
        ];
    }

    public function testFileShouldNotBeEncryptedWithoutPassword(): void
    {
        $filePath = $this->writeFile(null);

        self::assertSame('PK', file_get_contents($filePath, length: 2));
        self::assertSame([self::ROWS, [['Other sheet']]], $this->readFile($filePath, null));
    }

    public function testPasswordProtectedFileShouldRequireRightPassword(): void
    {
        $filePath = $this->writeFile('secret');

        foreach ([[null, PasswordRequiredException::class], ['Secret', InvalidPasswordException::class]] as [$password, $expectedException]) {
            try {
                $this->readFile($filePath, $password);
                self::fail('The file should not have been read');
            } catch (IOException $exception) {
                self::assertInstanceOf($expectedException, $exception->getPrevious());
            }
        }
    }

    #[DataProvider('provideInvalidPasswordCases')]
    public function testInvalidPassword(string $password): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The password must be a UTF-8 string of 1 to 255 characters.');

        new Options(password: $password);
    }

    public static function provideInvalidPasswordCases(): iterable
    {
        return [
            'empty' => [''],
            'not UTF-8' => ["secr\xE9t"],
            'too long' => [str_repeat('é', 256)],
            'too long, with a valid beginning' => ["\n".str_repeat('a', 255)],
            'too long, with a valid end' => [str_repeat('a', 255)."\n"],
        ];
    }

    public function testPasswordOption(): void
    {
        self::assertNull((new Options())->password);
        self::assertSame('secret', (new Options(password: 'secret'))->password);
    }

    private function writeFile(?string $password, bool $shouldUseInlineStrings = true): string
    {
        $filePath = (new TestUsingResource())->getGeneratedResourcePath(uniqid('encrypted_writer_').'.xlsx');

        $writer = new Writer(new Options(
            tempFolder: $this->tempFolder,
            SHOULD_USE_INLINE_STRINGS: $shouldUseInlineStrings,
            password: $password,
        ));
        $writer->openToFile($filePath);
        foreach (self::ROWS as $values) {
            $writer->addRow(Row::fromValues($values));
        }
        $writer->addNewSheetAndMakeItCurrent();
        $writer->addRow(Row::fromValues(['Other sheet']));
        $writer->close();

        return $filePath;
    }

    /**
     * @return list<list<array<mixed>>> Rows of every sheet
     */
    private function readFile(string $filePath, ?string $password): array
    {
        $sheets = [];
        $reader = new Reader(new ReaderOptions(tempFolder: $this->tempFolder, password: $password));
        $reader->open($filePath);
        foreach ($reader->getSheetIterator() as $sheet) {
            $rows = [];
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
            $sheets[] = $rows;
        }
        $reader->close();

        return $sheets;
    }
}
