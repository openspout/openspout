<?php

declare(strict_types=1);

namespace OpenSpout\Writer\ODS;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use DOMElement;
use DOMNode;
use finfo;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Helper\StringHelper;
use OpenSpout\Reader\Wrapper\XMLReader;
use OpenSpout\TestUsingResource;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\Common\Entity\Sheet;
use OpenSpout\Writer\Common\Manager\SheetManager;
use OpenSpout\Writer\Exception\SheetNotFoundException;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use OpenSpout\Writer\ODS\Manager\WorkbookManager;
use OpenSpout\Writer\RowCreationHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionHelper;

/**
 * @internal
 */
final class WriterTest extends TestCase
{
    use RowCreationHelper;

    public function testAddRowShouldThrowExceptionIfCannotOpenAFileForWriting(): void
    {
        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $fileName = 'file_that_wont_be_written.ods';
        $filePath = (new TestUsingResource())->getGeneratedUnwritableResourcePath($fileName);
        $this->expectException(IOException::class);

        @$writer->openToFile($filePath);
    }

    public function testAddRowShouldThrowExceptionIfCallAddRowBeforeOpeningWriter(): void
    {
        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $this->expectException(WriterNotOpenedException::class);

        $writer->addRow(Row::fromValues(['ods--11', 'ods--12']));
    }

    public function testAddRowShouldThrowExceptionIfCalledBeforeOpeningWriter(): void
    {
        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $this->expectException(WriterNotOpenedException::class);

        $writer->addRows([Row::fromValues(['ods--11', 'ods--12'])]);
    }

    public function testAddNewSheetAndMakeItCurrent(): void
    {
        $fileName = 'test_add_new_sheet_and_make_it_current.ods';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->addNewSheetAndMakeItCurrent();
        $writer->close();

        $sheets = $writer->getSheets();
        self::assertCount(2, $sheets, 'There should be 2 sheets');
        self::assertSame($sheets[1], $writer->getCurrentSheet(), 'The current sheet should be the second one.');
    }

    public function testSetCurrentSheet(): void
    {
        $fileName = 'test_set_current_sheet.ods';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $writer->addNewSheetAndMakeItCurrent();
        $writer->addNewSheetAndMakeItCurrent();

        $firstSheet = $writer->getSheets()[0];
        $writer->setCurrentSheet($firstSheet);

        $writer->close();

        self::assertSame($firstSheet, $writer->getCurrentSheet(), 'The current sheet should be the first one.');

        $dummySheet = new Sheet(
            random_int(100, 199),
            uniqid('foo_'),
            new SheetManager(StringHelper::factory())
        );

        $this->expectException(SheetNotFoundException::class);
        $writer->setCurrentSheet($dummySheet);
    }

    /**
     * @return array{0: ?string, 1: string}[]
     */
    public static function provideSetCreatorCases(): iterable
    {
        return [
            ['Test creator', 'Test creator'],
            [null, 'OpenSpout'],
        ];
    }

    /**
     * @dataProvider provideSetCreatorCases
     */
    public function testSetCreator(?string $expected, string $actual): void
    {
        $fileName = 'test_set_creator.ods';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        if (\is_string($expected)) {
            $writer->setCreator($expected);
        }
        $writer->openToFile($resourcePath);
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#meta.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);
        self::assertNotFalse($xmlContents);
        self::assertStringContainsString("<dc:creator>{$actual}</dc:creator>", $xmlContents);
    }

    public function testCloseShouldNoopWhenWriterIsNotOpened(): void
    {
        $fileName = 'test_double_close_calls.ods';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->close(); // This call should not cause any error

        $writer->openToFile($resourcePath);
        $writer->close();
        $writer->close(); // This call should not cause any error
        $this->expectNotToPerformAssertions();
    }

    public function testAddRowShouldWriteGivenDataToSheet(): void
    {
        $fileName = 'test_add_row_should_write_given_data_to_sheet.ods';
        $dataRows = $this->createRowsFromValues([
            ['ods--11', 'ods--12'],
            ['ods--21', 'ods--22', 'ods--23'],
        ]);

        $this->writeToODSFile($dataRows, $fileName);

        foreach ($dataRows as $dataRow) {
            foreach ($dataRow->getCells() as $cell) {
                $value = $cell->getValue();
                self::assertIsScalar($value);
                $this->assertValueWasWritten($fileName, (string) $value);
            }
        }
    }

    public function testAddRowShouldWriteGivenDataToTwoSheets(): void
    {
        $fileName = 'test_add_row_should_write_given_data_to_two_sheets.ods';
        $dataRows = $this->createRowsFromValues([
            ['ods--11', 'ods--12'],
            ['ods--21', 'ods--22', 'ods--23'],
        ]);

        $numSheets = 2;
        $this->writeToMultipleSheetsInODSFile($dataRows, $numSheets, $fileName);

        for ($i = 1; $i <= $numSheets; ++$i) {
            foreach ($dataRows as $dataRow) {
                foreach ($dataRow->getCells() as $cell) {
                    $value = $cell->getValue();
                    self::assertIsScalar($value);
                    $this->assertValueWasWritten($fileName, (string) $value);
                }
            }
        }
    }

    public function testAddRowShouldSupportAssociativeArrays(): void
    {
        $fileName = 'test_add_row_should_support_associative_arrays.ods';
        $dataRows = $this->createRowsFromValues([
            ['foo' => 'ods--11', 'bar' => 'ods--12'],
        ]);

        $this->writeToODSFile($dataRows, $fileName);

        foreach ($dataRows as $dataRow) {
            foreach ($dataRow->getCells() as $cell) {
                $value = $cell->getValue();
                self::assertIsScalar($value);
                $this->assertValueWasWritten($fileName, (string) $value);
            }
        }
    }

    public function testAddRowShouldSupportMultipleTypesOfData(): void
    {
        $fileName = 'test_add_row_should_support_multiple_types_of_data.ods';
        $dataRows = $this->createRowsFromValues([
            [
                'ods--11',
                true,
                '',
                0,
                10.2,
                null,
                new DateTimeImmutable('2020-03-04 05:06:07', new DateTimeZone('UTC')),
                new DateInterval('P1DT23S'),
            ],
        ]);

        $this->writeToODSFile($dataRows, $fileName);

        $this->assertValueWasWritten($fileName, 'ods--11');
        $this->assertValueWasWrittenToSheet($fileName, 1, 1); // true is converted to 1
        $this->assertValueWasWrittenToSheet($fileName, 1, 0);
        $this->assertValueWasWrittenToSheet($fileName, 1, 10.2);
        $this->assertValueWasWrittenToSheet($fileName, 1, '2020-03-04T05:06:07Z');
        $this->assertValueWasWrittenToSheet($fileName, 1, 'P1DT23S');
    }

    public function testAddRowShouldSupportFloatValuesInDifferentLocale(): void
    {
        $previousLocale = setlocale(LC_ALL, '0');
        self::assertNotFalse($previousLocale);

        try {
            // Pick a supported locale whose decimal point is a comma.
            // Installed locales differ from one system to another, so we can't pick
            // a given locale.
            $shell_exec = shell_exec('locale -a');
            if (!\is_string($shell_exec)) {
                self::markTestSkipped();
            }
            $supportedLocales = explode("\n", $shell_exec);
            $foundCommaLocale = false;
            foreach ($supportedLocales as $supportedLocale) {
                setlocale(LC_ALL, $supportedLocale);
                if (',' === localeconv()['decimal_point']) {
                    $foundCommaLocale = true;

                    break;
                }
            }

            if (!$foundCommaLocale) {
                self::markTestSkipped('No locale with comma decimal separator');
            }

            self::assertSame(',', localeconv()['decimal_point']);

            $fileName = 'test_add_row_should_support_float_values_in_different_locale.xlsx';
            $dataRows = $this->createRowsFromValues([
                [1234.5],
            ]);

            $this->writeToODSFile($dataRows, $fileName);

            $this->assertValueWasNotWrittenToSheet($fileName, 1, '1234,5');
            $this->assertValueWasWrittenToSheet($fileName, 1, '1234.5');
        } finally {
            // reset locale
            setlocale(LC_ALL, $previousLocale);
        }
    }

    public static function dataProviderForTestAddRowShouldUseNumberColumnsRepeatedForRepeatedValues(): array
    {
        return [
            [['ods--11', 'ods--11', 'ods--11'], 1, 3],
            [['', ''], 1, 2],
            [[true, true, true, true], 1, 4],
            [[1.1, 1.1], 1, 2],
            [['foo', 'bar'], 2, 0],
        ];
    }

    /**
     * @param mixed[] $dataRow
     */
    #[DataProvider('dataProviderForTestAddRowShouldUseNumberColumnsRepeatedForRepeatedValues')]
    public function testAddRowShouldUseNumberColumnsRepeatedForRepeatedValues(array $dataRow, int $expectedNumTableCells, int $expectedNumColumnsRepeated): void
    {
        $fileName = 'test_add_row_should_use_number_columns_repeated.ods';
        $this->writeToODSFile($this->createRowsFromValues([$dataRow]), $fileName);

        /** @var DOMElement $sheetXmlNode */
        $sheetXmlNode = $this->getSheetXmlNode($fileName, 1);
        $tableCellNodes = $sheetXmlNode->getElementsByTagName('table-cell');

        self::assertSame($expectedNumTableCells, $tableCellNodes->length);

        if (1 === $expectedNumTableCells) {
            $tableCellNode = $tableCellNodes->item(0);
            $numColumnsRepeated = (int) $tableCellNode->getAttribute('table:number-columns-repeated');
            self::assertSame($expectedNumColumnsRepeated, $numColumnsRepeated);
        } else {
            foreach ($tableCellNodes as $tableCellNode) {
                self::assertFalse($tableCellNode->hasAttribute('table:number-columns-repeated'));
            }
        }
    }

    public function testAddRowShouldSupportCellInError(): void
    {
        $fileName = 'test_add_row_should_support_cell_in_error.ods';

        $cell = new Cell\ErrorCell('#DIV/0', null);

        $row = new Row([$cell]);

        $this->writeToODSFile([$row], $fileName);

        $this->assertValueWasWritten($fileName, 'calcext:value-type="error"');
        $this->assertValueWasWritten($fileName, '<text:p>#DIV/0</text:p>');
    }

    public function testAddRowShouldWriteGivenDataToTheCorrectSheet(): void
    {
        $fileName = 'test_add_row_should_write_given_data_to_the_correct_sheet.ods';
        $dataRowsSheet1 = $this->createRowsFromValues([
            ['ods--sheet1--11', 'ods--sheet1--12'],
            ['ods--sheet1--21', 'ods--sheet1--22', 'ods--sheet1--23'],
        ]);
        $dataRowsSheet2 = $this->createRowsFromValues([
            ['ods--sheet2--11', 'ods--sheet2--12'],
            ['ods--sheet2--21', 'ods--sheet2--22', 'ods--sheet2--23'],
        ]);
        $dataRowsSheet1Again = $this->createRowsFromValues([
            ['ods--sheet1--31', 'ods--sheet1--32'],
            ['ods--sheet1--41', 'ods--sheet1--42', 'ods--sheet1--43'],
        ]);

        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $writer->addRows($dataRowsSheet1);

        $writer->addNewSheetAndMakeItCurrent();
        $writer->addRows($dataRowsSheet2);

        $firstSheet = $writer->getSheets()[0];
        $writer->setCurrentSheet($firstSheet);

        $writer->addRows($dataRowsSheet1Again);

        $writer->close();

        foreach ($dataRowsSheet1 as $dataRow) {
            foreach ($dataRow->getCells() as $cell) {
                $this->assertValueWasWrittenToSheet($fileName, 1, $cell->getValue(), 'Data should have been written in Sheet 1');
            }
        }
        foreach ($dataRowsSheet2 as $dataRow) {
            foreach ($dataRow->getCells() as $cell) {
                $this->assertValueWasWrittenToSheet($fileName, 2, $cell->getValue(), 'Data should have been written in Sheet 2');
            }
        }
        foreach ($dataRowsSheet1Again as $dataRow) {
            foreach ($dataRow->getCells() as $cell) {
                $this->assertValueWasWrittenToSheet($fileName, 1, $cell->getValue(), 'Data should have been written in Sheet 1');
            }
        }
    }

    public function testAddRowShouldAutomaticallyCreateNewSheetsIfMaxRowsReachedAndOptionTurnedOn(): void
    {
        $fileName = 'test_add_row_should_automatically_create_new_sheets_if_max_rows_reached_and_option_turned_on.ods';
        $dataRows = $this->createRowsFromValues([
            ['ods--sheet1--11', 'ods--sheet1--12'],
            ['ods--sheet1--21', 'ods--sheet1--22', 'ods--sheet1--23'],
            ['ods--sheet2--11', 'ods--sheet2--12'], // this should be written in a new sheet
        ]);

        // set the maxRowsPerSheet limit to 2
        ReflectionHelper::setStaticValue(WorkbookManager::class, 'maxRowsPerWorksheet', 2);

        $writer = $this->writeToODSFile($dataRows, $fileName, $shouldCreateSheetsAutomatically = true);
        self::assertCount(2, $writer->getSheets(), '2 sheets should have been created.');

        $this->assertValueWasNotWrittenToSheet($fileName, 1, 'ods--sheet2--11');
        $this->assertValueWasWrittenToSheet($fileName, 2, 'ods--sheet2--11');

        ReflectionHelper::reset();
    }

    public function testAddRowShouldNotCreateNewSheetsIfMaxRowsReachedAndOptionTurnedOff(): void
    {
        $fileName = 'test_add_row_should_not_create_new_sheets_if_max_rows_reached_and_option_turned_off.ods';
        $dataRows = $this->createRowsFromValues([
            ['ods--sheet1--11', 'ods--sheet1--12'],
            ['ods--sheet1--21', 'ods--sheet1--22', 'ods--sheet1--23'],
            ['ods--sheet1--31', 'ods--sheet1--32'], // this should NOT be written in a new sheet
        ]);

        // set the maxRowsPerSheet limit to 2
        ReflectionHelper::setStaticValue(WorkbookManager::class, 'maxRowsPerWorksheet', 2);

        $writer = $this->writeToODSFile($dataRows, $fileName, $shouldCreateSheetsAutomatically = false);
        self::assertCount(1, $writer->getSheets(), 'Only 1 sheet should have been created.');

        $this->assertValueWasNotWrittenToSheet($fileName, 1, 'ods--sheet1--31');

        ReflectionHelper::reset();
    }

    public function testAddRowShouldEscapeHtmlSpecialCharacters(): void
    {
        $fileName = 'test_add_row_should_escape_html_special_characters.ods';
        $dataRows = $this->createRowsFromValues([
            ['I\'m in "great" mood', 'This <must> be escaped & tested'],
        ]);

        $this->writeToODSFile($dataRows, $fileName);

        $this->assertValueWasWritten($fileName, 'I&#039;m in &quot;great&quot; mood', 'Quotes should be escaped');
        $this->assertValueWasWritten($fileName, 'This &lt;must&gt; be escaped &amp; tested', '<, > and & should be escaped');
    }

    public function testAddRowShouldKeepNewLines(): void
    {
        $fileName = 'test_add_row_should_keep_new_lines.ods';
        $dataRow = ["I have\na dream"];

        $this->writeToODSFile($this->createRowsFromValues([$dataRow]), $fileName);

        $this->assertValueWasWrittenToSheet($fileName, 1, 'I have');
        $this->assertValueWasWrittenToSheet($fileName, 1, 'a dream');
    }

    public function testGeneratedFileShouldHaveTheCorrectMimeType(): void
    {
        if (!\function_exists('finfo')) {
            self::markTestSkipped('finfo is not available on this system (possibly running on Windows where the DLL needs to be added explicitly to the php.ini)');
        }

        $fileName = 'test_mime_type.ods';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $dataRow = ['foo'];

        $this->writeToODSFile($this->createRowsFromValues([$dataRow]), $fileName);

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        self::assertSame('application/vnd.oasis.opendocument.spreadsheet', $finfo->file($resourcePath));
    }

    public function testShouldSetOptionWithGetter(): void
    {
        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);

        $options->DEFAULT_COLUMN_WIDTH = (float) random_int(100, 199);

        self::assertSame($options->DEFAULT_COLUMN_WIDTH, $writer->getOptions()->DEFAULT_COLUMN_WIDTH);
    }

    public function testSetAutofilterShouldWriteCorrectData(): void
    {
        $fileName = 'test_set_autofilter_should_write_correct_data.ods';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->getCurrentSheet()->setName('Sheet First');
        $autoFilter = new AutoFilter(0, 1, 3, 3);
        $writer->getCurrentSheet()->setAutoFilter($autoFilter);
        $writer->close();

        $xmlReader = new XMLReader();
        $xmlReader->openFileInZip($resourcePath, 'content.xml');
        $xmlReader->readUntilNodeFound('table:database-ranges');

        $sheetXmlNode = $xmlReader->expand();
        self::assertInstanceOf(DOMElement::class, $sheetXmlNode);

        $databaseRangeNodes = $sheetXmlNode->getElementsByTagName('database-range');
        self::assertSame(1, $databaseRangeNodes->length);

        $databaseRangeNode = $databaseRangeNodes->item(0);
        $targetRangeAddress = $databaseRangeNode->getAttribute('table:target-range-address');
        self::assertSame("'Sheet First'.A1:'Sheet First'.D3", $targetRangeAddress);
    }

    public function testRemoveAutofilterShouldDeleteDatabaseRangesTag(): void
    {
        $fileName = 'test_remove_autofilter_should_delete_all_database_range_tag.ods';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $autoFilter = new AutoFilter(0, 1, 3, 3);
        $writer->getCurrentSheet()->setAutoFilter($autoFilter);
        $writer->close();

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->getCurrentSheet()->setAutoFilter(null);
        $writer->close();

        $xmlReader = new XMLReader();
        $xmlReader->openFileInZip($resourcePath, 'content.xml');
        $foundDatabaseRanges = $xmlReader->readUntilNodeFound('table:database-ranges');
        self::assertFalse($foundDatabaseRanges);
    }

    public function testAddAutofilterToTwoSheetsShouldWriteCorrectData(): void
    {
        $fileName = 'test_add_autofilter_to_two_sheets_should-write-correct-data.ods';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $autoFilter1 = new AutoFilter(0, 1, 3, 3);
        $writer->getCurrentSheet()->setAutoFilter($autoFilter1);
        $writer->addNewSheetAndMakeItCurrent();
        $autoFilter2 = new AutoFilter(0, 1, 26, 11);
        $writer->getCurrentSheet()->setAutoFilter($autoFilter2);
        $writer->close();

        $xmlReader = new XMLReader();
        $xmlReader->openFileInZip($resourcePath, 'content.xml');
        $xmlReader->readUntilNodeFound('table:database-ranges');

        $sheetXmlNode = $xmlReader->expand();
        self::assertInstanceOf(DOMElement::class, $sheetXmlNode);

        $databaseRangeNodes = $sheetXmlNode->getElementsByTagName('database-range');
        self::assertSame(2, $databaseRangeNodes->length);

        $databaseRangeFirstNode = $databaseRangeNodes->item(0);
        $targetRangeAddress = $databaseRangeFirstNode->getAttribute('table:target-range-address');
        self::assertSame("'Sheet1'.A1:'Sheet1'.D3", $targetRangeAddress);

        $databaseRangeSecondNode = $databaseRangeNodes->item(1);
        $targetRangeAddress = $databaseRangeSecondNode->getAttribute('table:target-range-address');
        self::assertSame("'Sheet2'.A1:'Sheet2'.AA11", $targetRangeAddress);
    }

    /**
     * @param Row[] $allRows
     */
    private function writeToODSFile(
        array $allRows,
        string $fileName,
        ?bool $shouldCreateSheetsAutomatically = null
    ): Writer {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        if (null !== $shouldCreateSheetsAutomatically) {
            $options->SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY = $shouldCreateSheetsAutomatically;
        }
        $writer = new Writer($options);

        $writer->openToFile($resourcePath);
        $writer->addRows($allRows);
        $writer->close();

        return $writer;
    }

    /**
     * @param Row[] $allRows
     */
    private function writeToMultipleSheetsInODSFile(
        array $allRows,
        int $numSheets,
        string $fileName,
        ?bool $shouldCreateSheetsAutomatically = null
    ): Writer {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        if (null !== $shouldCreateSheetsAutomatically) {
            $options->SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY = $shouldCreateSheetsAutomatically;
        }
        $writer = new Writer($options);

        $writer->openToFile($resourcePath);
        $writer->addRows($allRows);

        for ($i = 1; $i < $numSheets; ++$i) {
            $writer->addNewSheetAndMakeItCurrent();
            $writer->addRows($allRows);
        }

        $writer->close();

        return $writer;
    }

    private function assertValueWasWritten(string $fileName, string $value, string $message = ''): void
    {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $pathToContentFile = $resourcePath.'#content.xml';
        $xmlContents = file_get_contents('zip://'.$pathToContentFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString($value, $xmlContents, $message);
    }

    /**
     * @param mixed $value
     */
    private function assertValueWasWrittenToSheet(string $fileName, int $sheetIndex, $value, string $message = ''): void
    {
        $sheetXmlAsString = $this->getSheetXmlNodeAsString($fileName, $sheetIndex);
        $valueAsXmlString = "<text:p>{$value}</text:p>";

        self::assertStringContainsString($valueAsXmlString, $sheetXmlAsString, $message);
    }

    /**
     * @param mixed $value
     */
    private function assertValueWasNotWrittenToSheet(string $fileName, int $sheetIndex, $value, string $message = ''): void
    {
        $sheetXmlAsString = $this->getSheetXmlNodeAsString($fileName, $sheetIndex);
        $valueAsXmlString = "<text:p>{$value}</text:p>";

        self::assertStringNotContainsString($valueAsXmlString, $sheetXmlAsString, $message);
    }

    private function getSheetXmlNode(string $fileName, int $sheetIndex): DOMNode
    {
        $xmlReader = $this->moveReaderToCorrectTableNode($fileName, $sheetIndex);

        $DOMNode = $xmlReader->expand();
        self::assertNotFalse($DOMNode);

        return $DOMNode;
    }

    private function getSheetXmlNodeAsString(string $fileName, int $sheetIndex): string
    {
        $xmlReader = $this->moveReaderToCorrectTableNode($fileName, $sheetIndex);

        return $xmlReader->readOuterXml();
    }

    private function moveReaderToCorrectTableNode(string $fileName, int $sheetIndex): XMLReader
    {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $xmlReader = new XMLReader();
        $xmlReader->openFileInZip($resourcePath, 'content.xml');
        $xmlReader->readUntilNodeFound('table:table');

        for ($i = 1; $i < $sheetIndex; ++$i) {
            $xmlReader->readUntilNodeFound('table:table');
        }

        return $xmlReader;
    }
}
