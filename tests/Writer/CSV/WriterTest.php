<?php

declare(strict_types=1);

namespace OpenSpout\Writer\CSV;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Helper\EncodingHelper;
use OpenSpout\TestUsingResource;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use OpenSpout\Writer\RowCreationHelper;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WriterTest extends TestCase
{
    use RowCreationHelper;

    public function testWriteShouldThrowExceptionIfCannotOpenFileForWriting(): void
    {
        $fileName = 'file_that_wont_be_written.csv';
        $filePath = (new TestUsingResource())->getGeneratedUnwritableResourcePath($fileName);

        $writer = new Writer();
        $writer->close();
        $this->expectException(IOException::class);
        $writer->openToFile($filePath);
    }

    public function testWriteShouldThrowExceptionIfCallAddRowBeforeOpeningWriter(): void
    {
        $writer = new Writer();
        $this->expectException(WriterNotOpenedException::class);

        $writer->addRow(Row::fromValues(['csv--11', 'csv--12']));
    }

    public function testWriteShouldThrowExceptionIfCallAddRowsBeforeOpeningWriter(): void
    {
        $writer = new Writer();
        $this->expectException(WriterNotOpenedException::class);

        $writer->addRow(Row::fromValues(['csv--11', 'csv--12']));
    }

    public function testCloseShouldNoopWhenWriterIsNotOpened(): void
    {
        $fileName = 'test_double_close_calls.csv';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $writer = new Writer();
        $writer->close(); // This call should not cause any error

        $writer->openToFile($resourcePath);
        $writer->close();
        $writer->close(); // This call should not cause any error
        $this->expectNotToPerformAssertions();
    }

    public function testWriteShouldAddUtf8Bom(): void
    {
        $allRows = $this->createRowsFromValues([
            ['csv--11', 'csv--12'],
        ]);
        $writtenContent = $this->writeToCsvFileAndReturnWrittenContent($allRows, 'csv_with_utf8_bom.csv');

        self::assertStringStartsWith(EncodingHelper::BOM_UTF8, $writtenContent, 'The CSV file should contain a UTF-8 BOM');
    }

    public function testWriteShouldNotAddUtf8Bom(): void
    {
        $allRows = $this->createRowsFromValues([
            ['csv--11', 'csv--12'],
        ]);
        $options = new Options();
        $options->FIELD_DELIMITER = ',';
        $options->FIELD_ENCLOSURE = '"';
        $options->SHOULD_ADD_BOM = false;
        $writtenContent = $this->writeToCsvFileAndReturnWrittenContent($allRows, 'csv_no_bom.csv', $options);

        self::assertStringNotContainsString(EncodingHelper::BOM_UTF8, $writtenContent, 'The CSV file should not contain a UTF-8 BOM');
    }

    public function testWriteShouldSupportNullValues(): void
    {
        $allRows = $this->createRowsFromValues([
            ['csv--11', null, 'csv--13'],
        ]);
        $writtenContent = $this->writeToCsvFileAndReturnWrittenContent($allRows, 'csv_with_null_values.csv');
        $writtenContent = $this->trimWrittenContent($writtenContent);

        self::assertSame('csv--11,,csv--13', $writtenContent, 'The null values should be replaced by empty values');
    }

    public function testWriteShouldSupportBooleanValues(): void
    {
        $allRows = $this->createRowsFromValues([
            [true, false],
        ]);
        $writtenContent = $this->writeToCsvFileAndReturnWrittenContent($allRows, 'csv_with_boolean_values.csv');
        $writtenContent = $this->trimWrittenContent($writtenContent);

        self::assertSame('1,0', $writtenContent);
    }

    public function testWriteShouldSupportFormulaLikeContent(): void
    {
        $allRows = $this->createRowsFromValues([
            ['=Testing='],
        ]);
        $writtenContent = $this->writeToCsvFileAndReturnWrittenContent($allRows, 'csv_with_null_values.csv');
        $writtenContent = $this->trimWrittenContent($writtenContent);

        self::assertSame('=Testing=', $writtenContent);
    }

    public function testWriteShouldNotSkipEmptyRows(): void
    {
        $allRows = $this->createRowsFromValues([
            ['csv--11', 'csv--12'],
            [],
            ['csv--31', 'csv--32'],
        ]);
        $writtenContent = $this->writeToCsvFileAndReturnWrittenContent($allRows, 'csv_with_empty_rows.csv');
        $writtenContent = $this->trimWrittenContent($writtenContent);

        self::assertSame("csv--11,csv--12\n\ncsv--31,csv--32", $writtenContent, 'Empty rows should be skipped');
    }

    public function testWriteShouldSupportCustomFieldDelimiter(): void
    {
        $allRows = $this->createRowsFromValues([
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22', 'csv--23'],
        ]);
        $options = new Options();
        $options->FIELD_DELIMITER = '|';
        $writtenContent = $this->writeToCsvFileAndReturnWrittenContent($allRows, 'csv_with_pipe_delimiters.csv', $options);
        $writtenContent = $this->trimWrittenContent($writtenContent);

        self::assertSame("csv--11|csv--12|csv--13\ncsv--21|csv--22|csv--23", $writtenContent, 'The fields should be delimited with |');
    }

    public function testFflush(): void
    {
        $allRows = $this->createRowsFromValues([
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22', 'csv--23'],
        ]);
        $options = new Options();
        $options->FLUSH_THRESHOLD = 1;
        $writtenContent = $this->writeToCsvFileAndReturnWrittenContent($allRows, 'csv_fflush.csv', $options);
        $writtenContent = $this->trimWrittenContent($writtenContent);

        self::assertSame("csv--11,csv--12,csv--13\ncsv--21,csv--22,csv--23", $writtenContent);
    }

    public function testWriteShouldSupportCustomFieldEnclosure(): void
    {
        $allRows = $this->createRowsFromValues([
            ['This is, a comma', 'csv--12', 'csv--13'],
        ]);
        $options = new Options();
        $options->FIELD_DELIMITER = ',';
        $options->FIELD_ENCLOSURE = '#';
        $writtenContent = $this->writeToCsvFileAndReturnWrittenContent($allRows, 'csv_with_pound_enclosures.csv', $options);
        $writtenContent = $this->trimWrittenContent($writtenContent);

        self::assertSame('#This is, a comma#,csv--12,csv--13', $writtenContent, 'The fields should be enclosed with #');
    }

    public function testWriteShouldSupportedEscapedCharacters(): void
    {
        $allRows = $this->createRowsFromValues([
            ['"csv--11"', 'csv--12\\', 'csv--13\\\\', 'csv--14\\\\\\'],
        ]);
        $writtenContent = $this->writeToCsvFileAndReturnWrittenContent($allRows, 'csv_with_escaped_characters.csv');
        $writtenContent = $this->trimWrittenContent($writtenContent);

        self::assertSame('"""csv--11""",csv--12\\,csv--13\\\\,csv--14\\\\\\', $writtenContent, 'The \'"\' and \'\\\' characters should be properly escaped');
    }

    public function testShouldSetOptionWithGetter(): void
    {
        $options = new Options();
        $writer = new Writer($options);

        $options->FLUSH_THRESHOLD = random_int(100, 199);

        self::assertSame($options->FLUSH_THRESHOLD, $writer->getOptions()->FLUSH_THRESHOLD);
    }

    public function testWriteToCompressedStream(): void
    {
        $fileName = 'csv_compressed.csv.gz';
        $pathName = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $resourcePath = 'compress.zlib://'.$pathName;

        $writer = new Writer();
        $writer->getOptions()->SHOULD_ADD_BOM = false;
        $writer->openToFile($resourcePath);
        $writer->addRows($this->createRowsFromValues([
            ['csv-1', 'csv-2', 'csv-3'],
        ]));
        $writer->close();

        $writtenContent = file_get_contents($pathName);
        self::assertNotFalse($writtenContent);
        $content = gzdecode($writtenContent);
        self::assertNotFalse($content);
        $content = trim($content);
        self::assertSame('csv-1,csv-2,csv-3', $content);
    }

    public function testShouldReturnWrittenRowCount(): void
    {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath('row_count.csv');

        $writer = new Writer();
        self::assertSame(0, $writer->getWrittenRowCount());
        $writer->openToFile($resourcePath);
        self::assertSame(0, $writer->getWrittenRowCount());
        $writer->addRow(Row::fromValues(['csv-1', null]));
        self::assertSame(1, $writer->getWrittenRowCount());
        $writer->addRow(Row::fromValues(['csv-2', null]));
        self::assertSame(2, $writer->getWrittenRowCount());
        $writer->addRows($this->createRowsFromValues([
            ['csv--11', 'csv--12'],
            [],
            ['csv--31', 'csv--32'],
        ]));
        self::assertSame(5, $writer->getWrittenRowCount());
        $writer->close();
        self::assertSame(5, $writer->getWrittenRowCount());
    }

    /**
     * @param Row[] $allRows
     */
    private function writeToCsvFileAndReturnWrittenContent(
        array $allRows,
        string $fileName,
        ?Options $options = null
    ): string {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->addRows($allRows);
        $writer->close();

        $file_get_contents = file_get_contents($resourcePath);
        self::assertNotFalse($file_get_contents);

        return $file_get_contents;
    }

    private function trimWrittenContent(string $writtenContent): string
    {
        // remove line feeds and UTF-8 BOM
        return trim($writtenContent, PHP_EOL.EncodingHelper::BOM_UTF8);
    }
}
