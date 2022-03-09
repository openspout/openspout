<?php

namespace OpenSpout\Writer\CSV;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Helper\EncodingHelper;
use OpenSpout\TestUsingResource;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use OpenSpout\Writer\RowCreationHelper;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WriterTest extends TestCase
{
    use RowCreationHelper;
    use TestUsingResource;

    public function testWriteShouldThrowExceptionIfCannotOpenFileForWriting()
    {
        $this->expectException(IOException::class);

        $fileName = 'file_that_wont_be_written.csv';
        $this->createUnwritableFolderIfNeeded();
        $filePath = $this->getGeneratedUnwritableResourcePath($fileName);

        $writer = WriterEntityFactory::createCSVWriter();
        @$writer->openToFile($filePath);
        $writer->addRow($this->createRowFromValues(['csv--11', 'csv--12']));
        $writer->close();
    }

    public function testWriteShouldThrowExceptionIfCallAddRowBeforeOpeningWriter()
    {
        $this->expectException(WriterNotOpenedException::class);

        $writer = WriterEntityFactory::createCSVWriter();
        $writer->addRow($this->createRowFromValues(['csv--11', 'csv--12']));
        $writer->close();
    }

    public function testWriteShouldThrowExceptionIfCallAddRowsBeforeOpeningWriter()
    {
        $this->expectException(WriterNotOpenedException::class);

        $writer = WriterEntityFactory::createCSVWriter();
        $writer->addRow($this->createRowFromValues(['csv--11', 'csv--12']));
        $writer->close();
    }

    public function testAddRowsShouldThrowExceptionIfRowsAreNotArrayOfArrays()
    {
        $fileName = 'test_non_array_values.csv';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createCSVWriter();
        $writer->openToFile($resourcePath);

        $this->expectException(InvalidArgumentException::class);
        $writer->addRows([['csv--11', 'csv--12']]);
        $writer->close();
    }

    public function testCloseShouldNoopWhenWriterIsNotOpened()
    {
        $fileName = 'test_double_close_calls.csv';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createCSVWriter();
        $writer->close(); // This call should not cause any error

        $writer->openToFile($resourcePath);
        $writer->close();
        $writer->close(); // This call should not cause any error
        $this->expectNotToPerformAssertions();
    }

    public function testWriteShouldAddUtf8Bom()
    {
        $allRows = $this->createRowsFromValues([
            ['csv--11', 'csv--12'],
        ]);
        $writtenContent = $this->writeToCsvFileAndReturnWrittenContent($allRows, 'csv_with_utf8_bom.csv');

        static::assertStringStartsWith(EncodingHelper::BOM_UTF8, $writtenContent, 'The CSV file should contain a UTF-8 BOM');
    }

    public function testWriteShouldNotAddUtf8Bom()
    {
        $allRows = $this->createRowsFromValues([
            ['csv--11', 'csv--12'],
        ]);
        $writtenContent = $this->writeToCsvFileAndReturnWrittenContent($allRows, 'csv_no_bom.csv', ',', '"', false);

        static::assertStringNotContainsString(EncodingHelper::BOM_UTF8, $writtenContent, 'The CSV file should not contain a UTF-8 BOM');
    }

    public function testWriteShouldSupportNullValues()
    {
        $allRows = $this->createRowsFromValues([
            ['csv--11', null, 'csv--13'],
        ]);
        $writtenContent = $this->writeToCsvFileAndReturnWrittenContent($allRows, 'csv_with_null_values.csv');
        $writtenContent = $this->trimWrittenContent($writtenContent);

        static::assertSame('csv--11,,csv--13', $writtenContent, 'The null values should be replaced by empty values');
    }

    public function testWriteShouldNotSkipEmptyRows()
    {
        $allRows = $this->createRowsFromValues([
            ['csv--11', 'csv--12'],
            [],
            ['csv--31', 'csv--32'],
        ]);
        $writtenContent = $this->writeToCsvFileAndReturnWrittenContent($allRows, 'csv_with_empty_rows.csv');
        $writtenContent = $this->trimWrittenContent($writtenContent);

        static::assertSame("csv--11,csv--12\n\ncsv--31,csv--32", $writtenContent, 'Empty rows should be skipped');
    }

    public function testWriteShouldSupportCustomFieldDelimiter()
    {
        $allRows = $this->createRowsFromValues([
            ['csv--11', 'csv--12', 'csv--13'],
            ['csv--21', 'csv--22', 'csv--23'],
        ]);
        $writtenContent = $this->writeToCsvFileAndReturnWrittenContent($allRows, 'csv_with_pipe_delimiters.csv', '|');
        $writtenContent = $this->trimWrittenContent($writtenContent);

        static::assertSame("csv--11|csv--12|csv--13\ncsv--21|csv--22|csv--23", $writtenContent, 'The fields should be delimited with |');
    }

    public function testWriteShouldSupportCustomFieldEnclosure()
    {
        $allRows = $this->createRowsFromValues([
            ['This is, a comma', 'csv--12', 'csv--13'],
        ]);
        $writtenContent = $this->writeToCsvFileAndReturnWrittenContent($allRows, 'csv_with_pound_enclosures.csv', ',', '#');
        $writtenContent = $this->trimWrittenContent($writtenContent);

        static::assertSame('#This is, a comma#,csv--12,csv--13', $writtenContent, 'The fields should be enclosed with #');
    }

    public function testWriteShouldSupportedEscapedCharacters()
    {
        $allRows = $this->createRowsFromValues([
            ['"csv--11"', 'csv--12\\', 'csv--13\\\\', 'csv--14\\\\\\'],
        ]);
        $writtenContent = $this->writeToCsvFileAndReturnWrittenContent($allRows, 'csv_with_escaped_characters.csv');
        $writtenContent = $this->trimWrittenContent($writtenContent);

        static::assertSame('"""csv--11""",csv--12\\,csv--13\\\\,csv--14\\\\\\', $writtenContent, 'The \'"\' and \'\\\' characters should be properly escaped');
    }

    /**
     * @param Row[]  $allRows
     * @param string $fileName
     * @param string $fieldDelimiter
     * @param string $fieldEnclosure
     * @param bool   $shouldAddBOM
     *
     * @return string
     */
    private function writeToCsvFileAndReturnWrittenContent($allRows, $fileName, $fieldDelimiter = ',', $fieldEnclosure = '"', $shouldAddBOM = true)
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createCSVWriter();
        $writer->setFieldDelimiter($fieldDelimiter);
        $writer->setFieldEnclosure($fieldEnclosure);
        $writer->setShouldAddBOM($shouldAddBOM);

        $writer->openToFile($resourcePath);
        $writer->addRows($allRows);
        $writer->close();

        return file_get_contents($resourcePath);
    }

    /**
     * @param string $writtenContent
     *
     * @return string
     */
    private function trimWrittenContent($writtenContent)
    {
        // remove line feeds and UTF-8 BOM
        return trim($writtenContent, PHP_EOL.EncodingHelper::BOM_UTF8);
    }
}
