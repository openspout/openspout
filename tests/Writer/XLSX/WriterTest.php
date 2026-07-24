<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use DOMDocument;
use DOMElement;
use finfo;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Comment\Comment;
use OpenSpout\Common\Entity\Comment\TextRun;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\TextRunVerticalStyle;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Reader\Wrapper\XMLReader;
use OpenSpout\TestUsingResource;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\Common\Helper\ImageHelperTrait;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use OpenSpout\Writer\XLSX\Manager\WorkbookManager;
use OpenSpout\Writer\XLSX\Options\HeaderFooter;
use OpenSpout\Writer\XLSX\Options\PageMargin;
use OpenSpout\Writer\XLSX\Options\PageOrientation;
use OpenSpout\Writer\XLSX\Options\PageSetup;
use OpenSpout\Writer\XLSX\Options\PaperSize;
use OpenSpout\Writer\XLSX\Options\SheetProtection;
use OpenSpout\Writer\XLSX\Options\WorkbookProtection;
use OpenSpout\Writer\XLSX\Validation\CellReference;
use OpenSpout\Writer\XLSX\Validation\ErrorStyle;
use OpenSpout\Writer\XLSX\Validation\Rules\ListValidationRule;
use OpenSpout\Writer\XLSX\Validation\Rules\WholeNumberValidationRule;
use OpenSpout\Writer\XLSX\Validation\ValidationDisplay;
use OpenSpout\Writer\XLSX\Validation\ValidationOperator;
use PHPUnit\Framework\TestCase;
use ReflectionHelper;
use ZipArchive;

/**
 * @internal
 */
final class WriterTest extends TestCase
{
    use ImageHelperTrait;

    public function testWriteTextRunFormatting(): void
    {
        $fileName = 'test_text_run_formatting.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());

        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $row = new Row([
            Cell::fromValue([
                new TextRun('bold', bold: true),
                new TextRun('superscript', verticalStyle: TextRunVerticalStyle::SUPERSCRIPT),
                new TextRun('subscript', verticalStyle: TextRunVerticalStyle::SUBSCRIPT),
                new TextRun('fontSize', fontSize: 12),
                new TextRun('fontCalibri', fontName: 'Calibri'),
                new TextRun('italic', italic: true),
                new TextRun('fontColor', fontColor: '000001'),
            ]),
        ]);

        $writer->addRow($row);
        $writer->close();

        // Now test if the resources contain what we need
        $pathToSheetFile = $resourcePath.'#xl/sharedStrings.xml';
        $xmlContents = file_get_contents('zip://'.$pathToSheetFile);
        self::assertNotFalse($xmlContents);
        $xml = new DOMDocument();
        self::assertTrue($xml->loadXML($xmlContents), 'sharedStrings is valid XML');

        self::assertStringContainsString('<b/>', $xmlContents, 'Text run does not format bold');
        self::assertStringContainsString('<i/>', $xmlContents, 'Text run does not format italic');
        self::assertStringContainsString('<sz val="12"/>', $xmlContents, 'Text run does not format font size');
        self::assertStringContainsString('<vertAlign val="superscript"/>', $xmlContents, 'Text run does not format superscript text');
        self::assertStringContainsString('<vertAlign val="subscript"/>', $xmlContents, 'Text run does not format subscript text');
        self::assertStringContainsString('<rFont val="Calibri"/>', $xmlContents, 'Text run does not format font');
        self::assertStringContainsString('<color rgb="000001"/>', $xmlContents, 'Text run does not format font color');
    }

    public function testAddRowShouldThrowExceptionIfCannotOpenAFileForWriting(): void
    {
        $fileName = 'file_that_wont_be_written.xlsx';
        $filePath = (new TestUsingResource())->getGeneratedUnwritableResourcePath($fileName);

        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);

        $this->expectException(IOException::class);
        @$writer->openToFile($filePath);
    }

    public function testAddRowShouldThrowExceptionIfCallAddRowBeforeOpeningWriter(): void
    {
        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $this->expectException(WriterNotOpenedException::class);

        $writer->addRow(Row::fromValues(['xlsx--11', 'xlsx--12']));
    }

    public function testAddRowShouldThrowExceptionIfCalledBeforeOpeningWriter(): void
    {
        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $this->expectException(WriterNotOpenedException::class);

        $writer->addRows([Row::fromValues(['xlsx--11', 'xlsx--12'])]);
    }

    public function testAddNewSheetAndMakeItCurrent(): void
    {
        $fileName = 'test_add_new_sheet_and_make_it_current.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
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
        $fileName = 'test_set_current_sheet.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $writer->addNewSheetAndMakeItCurrent();
        $writer->addNewSheetAndMakeItCurrent();

        $firstSheet = $writer->getSheets()[0];
        $writer->setCurrentSheet($firstSheet);

        $writer->close();

        self::assertSame($firstSheet, $writer->getCurrentSheet(), 'The current sheet should be the first one.');
    }

    public function testCloseShouldNoopWhenWriterIsNotOpened(): void
    {
        $fileName = 'test_double_close_calls.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->close(); // This call should not cause any error

        $writer->openToFile($resourcePath);
        $writer->close();
        $writer->close(); // This call should not cause any error
        $this->expectNotToPerformAssertions();
    }

    public function testDefaultProperties(): void
    {
        $fileName = 'test_default_properties.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->close();

        $appXmlContents = file_get_contents('zip://'.$resourcePath.'#docProps/app.xml');
        self::assertNotFalse($appXmlContents);
        self::assertStringContainsString('<Application>OpenSpout</Application>', $appXmlContents);

        $coreXmlContents = file_get_contents('zip://'.$resourcePath.'#docProps/core.xml');
        self::assertNotFalse($coreXmlContents);
        self::assertStringContainsString('<dc:title>Untitled Spreadsheet</dc:title>', $coreXmlContents);
        self::assertStringContainsString('<dc:creator>OpenSpout</dc:creator>', $coreXmlContents);
        self::assertStringContainsString('<cp:lastModifiedBy>OpenSpout</cp:lastModifiedBy>', $coreXmlContents);

        self::assertFileDoesNotExist($resourcePath.'#docProps/custom.xml');

        $contentTypesXmlContents = file_get_contents('zip://'.$resourcePath.'#[Content_Types].xml');
        self::assertNotFalse($contentTypesXmlContents);
        self::assertStringNotContainsString('<Override ContentType="application/vnd.openxmlformats-officedocument.custom-properties+xml" PartName="/docProps/custom.xml" />', $contentTypesXmlContents);

        $relsXmlContents = file_get_contents('zip://'.$resourcePath.'#_rels/.rels');
        self::assertNotFalse($relsXmlContents);
        self::assertStringNotContainsString('<Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/custom-properties" Target="docProps/custom.xml"/>', $relsXmlContents);
    }

    public function testSetProperties(): void
    {
        $fileName = 'test_set_properties.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(
            tempFolder: (new TestUsingResource())->getTempFolderPath(),
            properties: new Properties(
                'Title',
                'Subject',
                'Application',
                'Creator',
                'Last Modified By',
                'key,words',
                'Description',
                'Category',
                'English',
            ),
        );
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->close();

        $appXmlContents = file_get_contents('zip://'.$resourcePath.'#docProps/app.xml');
        self::assertNotFalse($appXmlContents);
        self::assertStringContainsString('<Application>Application</Application>', $appXmlContents);

        $coreXmlContents = file_get_contents('zip://'.$resourcePath.'#docProps/core.xml');
        self::assertNotFalse($coreXmlContents);
        self::assertStringContainsString('<dc:title>Title</dc:title>', $coreXmlContents);
        self::assertStringContainsString('<dc:subject>Subject</dc:subject>', $coreXmlContents);
        self::assertStringContainsString('<dc:creator>Creator</dc:creator>', $coreXmlContents);
        self::assertStringContainsString('<cp:lastModifiedBy>Last Modified By</cp:lastModifiedBy>', $coreXmlContents);
        self::assertStringContainsString('<cp:keywords>key,words</cp:keywords>', $coreXmlContents);
        self::assertStringContainsString('<dc:description>Description</dc:description>', $coreXmlContents);
        self::assertStringContainsString('<cp:category>Category</cp:category>', $coreXmlContents);
        self::assertStringContainsString('<dc:language>English</dc:language>', $coreXmlContents);
    }

    public function testSetCustomProperties(): void
    {
        $fileName = 'test_set_custom_properties.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(
            tempFolder: (new TestUsingResource())->getTempFolderPath(),
            properties: new Properties(
                customProperties: [
                    'test' => 'Test',
                ]
            ),
        );
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#docProps/custom.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);
        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('<property fmtid="{D5CDD505-2E9C-101B-9397-08002B2CF9AE}" pid="2" name="test"><vt:lpwstr>Test</vt:lpwstr></property>', $xmlContents);

        $contentTypesXmlContents = file_get_contents('zip://'.$resourcePath.'#[Content_Types].xml');
        self::assertNotFalse($contentTypesXmlContents);
        self::assertStringContainsString('<Override ContentType="application/vnd.openxmlformats-officedocument.custom-properties+xml" PartName="/docProps/custom.xml" />', $contentTypesXmlContents);

        $relsXmlContents = file_get_contents('zip://'.$resourcePath.'#_rels/.rels');
        self::assertNotFalse($relsXmlContents);
        self::assertStringContainsString('<Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/custom-properties" Target="docProps/custom.xml"/>', $relsXmlContents);
    }

    public function testAddRowShouldWriteGivenDataToSheetUsingInlineStrings(): void
    {
        $fileName = 'test_add_row_should_write_given_data_to_sheet_using_inline_strings.xlsx';
        $dataRows = [
            Row::fromValues(['xlsx--11', 'xlsx--12']),
            Row::fromValues(['xlsx--21', 'xlsx--22', 'xlsx--23']),
        ];

        $this->writeToXLSXFile($dataRows, $fileName, true);

        foreach ($dataRows as $dataRow) {
            foreach ($dataRow->cells as $cell) {
                $this->assertInlineDataWasWrittenToSheet($fileName, 1, $cell->getValue());
            }
        }
    }

    public function testAddRowShouldWriteGivenDataToTwoSheetsUsingInlineStrings(): void
    {
        $fileName = 'test_add_row_should_write_given_data_to_two_sheets_using_inline_strings.xlsx';
        $dataRows = [
            Row::fromValues(['xlsx--11', 'xlsx--12']),
            Row::fromValues(['xlsx--21', 'xlsx--22', 'xlsx--23']),
        ];

        $numSheets = 2;
        $this->writeToMultipleSheetsInXLSXFile($dataRows, $numSheets, $fileName, true);

        for ($i = 1; $i <= $numSheets; ++$i) {
            foreach ($dataRows as $dataRow) {
                foreach ($dataRow->cells as $cell) {
                    $this->assertInlineDataWasWrittenToSheet($fileName, $numSheets, $cell->getValue());
                }
            }
        }
    }

    public function testAddRowShouldWriteGivenDataToSheetUsingSharedStrings(): void
    {
        $fileName = 'test_add_row_should_write_given_data_to_sheet_using_shared_strings.xlsx';
        $dataRows = [
            Row::fromValues(['xlsx--11', 'xlsx--12']),
            Row::fromValues(['xlsx--21', 'xlsx--22', 'xlsx--23']),
        ];

        $this->writeToXLSXFile($dataRows, $fileName, false);

        foreach ($dataRows as $dataRow) {
            foreach ($dataRow->cells as $cell) {
                $value = $cell->getValue();
                self::assertIsScalar($value);
                $this->assertSharedStringWasWritten($fileName, (string) $value);
            }
        }
    }

    public function testAddRowShouldWriteGivenDataToTwoSheetsUsingSharedStrings(): void
    {
        $fileName = 'test_add_row_should_write_given_data_to_two_sheets_using_shared_strings.xlsx';
        $dataRows = [
            Row::fromValues(['xlsx--11', 'xlsx--12']),
            Row::fromValues(['xlsx--21', 'xlsx--22', 'xlsx--23']),
        ];

        $numSheets = 2;
        $this->writeToMultipleSheetsInXLSXFile($dataRows, $numSheets, $fileName, false);

        for ($i = 1; $i <= $numSheets; ++$i) {
            foreach ($dataRows as $dataRow) {
                foreach ($dataRow->cells as $cell) {
                    $value = $cell->getValue();
                    self::assertIsScalar($value);
                    $this->assertSharedStringWasWritten($fileName, (string) $value);
                }
            }
        }
    }

    public function testAddRowShouldNotWriteEmptyRows(): void
    {
        $fileName = 'test_add_row_should_not_write_empty_rows.xlsx';
        $dataRows = [
            Row::fromValues(['']),
            Row::fromValues(['xlsx--21', 'xlsx--22']),
            Row::fromValues(['']),
            Row::fromValues(['']),
            Row::fromValues(['xlsx--51', 'xlsx--52']),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $this->assertInlineDataWasWrittenToSheet($fileName, 1, 'row r="2"');
        $this->assertInlineDataWasWrittenToSheet($fileName, 1, 'row r="5"');
        $this->assertInlineDataWasNotWrittenToSheet($fileName, 1, 'row r="1"');
        $this->assertInlineDataWasNotWrittenToSheet($fileName, 1, 'row r="3"');
        $this->assertInlineDataWasNotWrittenToSheet($fileName, 1, 'row r="4"');
    }

    public function testAddRowShouldSupportMultipleTypesOfData(): void
    {
        $fileName = 'test_add_row_should_support_multiple_types_of_data.xlsx';
        $dataRows = [
            Row::fromValues([
                'xlsx--11',
                true,
                '',
                0,
                10.2,
                null,
                new DateTimeImmutable('2020-03-04 06:00:00', new DateTimeZone('UTC')),
            ]),
        ];

        $this->writeToXLSXFile($dataRows, $fileName, false);

        $this->assertSharedStringWasWritten($fileName, 'xlsx--11');
        $this->assertInlineDataWasWrittenToSheet($fileName, 1, 1); // true is converted to 1
        $this->assertInlineDataWasWrittenToSheet($fileName, 1, 0);
        $this->assertInlineDataWasWrittenToSheet($fileName, 1, 10.2);
        $this->assertInlineDataWasWrittenToSheet($fileName, 1, 43894.25);
    }

    public function testAddRowShouldSupportCellInError(): void
    {
        $fileName = 'test_add_row_should_support_cell_in_error.xlsx';

        $cell = new Cell\ErrorCell('#DIV/0', null);

        $row = new Row([$cell]);

        $this->writeToXLSXFile([$row], $fileName);

        $this->assertInlineDataWasWrittenToSheet($fileName, 1, 't="e"><v>#DIV/0</v>');
    }

    public function testAddRowShouldWriteGivenDataToTheCorrectSheet(): void
    {
        $fileName = 'test_add_row_should_write_given_data_to_the_correct_sheet.xlsx';
        $dataRowsSheet1 = [
            Row::fromValues(['xlsx--sheet1--11', 'xlsx--sheet1--12']),
            Row::fromValues(['xlsx--sheet1--21', 'xlsx--sheet1--22', 'xlsx--sheet1--23']),
        ];
        $dataRowsSheet2 = [
            Row::fromValues(['xlsx--sheet2--11', 'xlsx--sheet2--12']),
            Row::fromValues(['xlsx--sheet2--21', 'xlsx--sheet2--22', 'xlsx--sheet2--23']),
        ];
        $dataRowsSheet1Again = [
            Row::fromValues(['xlsx--sheet1--31', 'xlsx--sheet1--32']),
            Row::fromValues(['xlsx--sheet1--41', 'xlsx--sheet1--42', 'xlsx--sheet1--43']),
        ];

        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(
            tempFolder: (new TestUsingResource())->getTempFolderPath(),
            SHOULD_USE_INLINE_STRINGS: true,
        );
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
            foreach ($dataRow->cells as $cell) {
                $this->assertInlineDataWasWrittenToSheet($fileName, 1, $cell->getValue(), 'Data should have been written in Sheet 1');
            }
        }
        foreach ($dataRowsSheet2 as $dataRow) {
            foreach ($dataRow->cells as $cell) {
                $this->assertInlineDataWasWrittenToSheet($fileName, 2, $cell->getValue(), 'Data should have been written in Sheet 2');
            }
        }
        foreach ($dataRowsSheet1Again as $dataRow) {
            foreach ($dataRow->cells as $cell) {
                $this->assertInlineDataWasWrittenToSheet($fileName, 1, $cell->getValue(), 'Data should have been written in Sheet 1');
            }
        }
    }

    public function testAddRowShouldAutomaticallyCreateNewSheetsIfMaxRowsReachedAndOptionTurnedOn(): void
    {
        $fileName = 'test_add_row_should_automatically_create_new_sheets_if_max_rows_reached_and_option_turned_on.xlsx';
        $dataRows = [
            Row::fromValues(['xlsx--sheet1--11', 'xlsx--sheet1--12']),
            Row::fromValues(['xlsx--sheet1--21', 'xlsx--sheet1--22', 'xlsx--sheet1--23']),
            Row::fromValues(['xlsx--sheet2--11', 'xlsx--sheet2--12']), // this should be written in a new sheet
        ];

        // set the maxRowsPerSheet limit to 2
        ReflectionHelper::setStaticValue(WorkbookManager::class, 'maxRowsPerWorksheet', 2);

        $writer = $this->writeToXLSXFile($dataRows, $fileName, true, $shouldCreateSheetsAutomatically = true);
        self::assertCount(2, $writer->getSheets(), '2 sheets should have been created.');

        $this->assertInlineDataWasNotWrittenToSheet($fileName, 1, 'xlsx--sheet2--11');
        $this->assertInlineDataWasWrittenToSheet($fileName, 2, 'xlsx--sheet2--11');

        ReflectionHelper::reset();
    }

    public function testAddRowShouldNotCreateNewSheetsIfMaxRowsReachedAndOptionTurnedOff(): void
    {
        $fileName = 'test_add_row_should_not_create_new_sheets_if_max_rows_reached_and_option_turned_off.xlsx';
        $dataRows = [
            Row::fromValues(['xlsx--sheet1--11', 'xlsx--sheet1--12']),
            Row::fromValues(['xlsx--sheet1--21', 'xlsx--sheet1--22', 'xlsx--sheet1--23']),
            Row::fromValues(['xlsx--sheet1--31', 'xlsx--sheet1--32']), // this should NOT be written in a new sheet
        ];

        // set the maxRowsPerSheet limit to 2
        ReflectionHelper::setStaticValue(WorkbookManager::class, 'maxRowsPerWorksheet', 2);

        $writer = $this->writeToXLSXFile($dataRows, $fileName, true, $shouldCreateSheetsAutomatically = false);
        self::assertCount(1, $writer->getSheets(), 'Only 1 sheet should have been created.');

        $this->assertInlineDataWasNotWrittenToSheet($fileName, 1, 'xlsx--sheet1--31');

        ReflectionHelper::reset();
    }

    public function testAddRowShouldEscapeHtmlSpecialCharacters(): void
    {
        $fileName = 'test_add_row_should_escape_html_special_characters.xlsx';
        $dataRows = [
            Row::fromValues(['I\'m in "great" mood', 'This <must> be escaped & tested']),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $this->assertInlineDataWasWrittenToSheet($fileName, 1, 'I&#039;m in &quot;great&quot; mood', 'Quotes should be escaped');
        $this->assertInlineDataWasWrittenToSheet($fileName, 1, 'This &lt;must&gt; be escaped &amp; tested', '<, > and & should be escaped');
    }

    public function testAddRowShouldEscapeControlCharacters(): void
    {
        $fileName = 'test_add_row_should_escape_control_characters.xlsx';
        $dataRows = [
            Row::fromValues(['control '.\chr(21).' character']),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $this->assertInlineDataWasWrittenToSheet($fileName, 1, 'control _x0015_ character');
    }

    public function testAddRowShouldApplyHeight(): void
    {
        $fileName = 'test_add_row_should_apply_height.xlsx';

        $this->writeToXLSXFile([Row::fromValues(['xlsx--11'], 25)], $fileName);

        $xmlReader = $this->getXmlReaderForSheetFromXmlFile($fileName, '1');

        $xmlReader->readUntilNodeFound('row');
        $DOMNode = $xmlReader->expand();
        self::assertInstanceOf(DOMElement::class, $DOMNode);
        self::assertSame('25', $DOMNode->getAttribute('ht'), 'Row height does not equal given value.');
        self::assertSame('1', $DOMNode->getAttribute('customHeight'), 'Row does not have custom height flag set.');
    }

    public function testCloseShouldAddMergeCellTags(): void
    {
        $fileName = 'test_add_row_should_support_column_widths.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $options->mergeCells(0, 1, 3, 1);
        $options->mergeCells(2, 3, 10, 3);
        $writer->close();

        $xmlReader = $this->getXmlReaderForSheetFromXmlFile($fileName, '1');
        $xmlReader->readUntilNodeFound('mergeCells');
        self::assertSame('mergeCells', $xmlReader->getCurrentNodeName(), 'Sheet does not have mergeCells tag');
        $DOMNode2 = $xmlReader->expand();
        self::assertNotFalse($DOMNode2);
        self::assertSame(2, $DOMNode2->childNodes->length, 'Sheet does not have the specified number of mergeCell definitions');
        $xmlReader->readUntilNodeFound('mergeCell');
        $DOMNode = $xmlReader->expand();
        self::assertInstanceOf(DOMElement::class, $DOMNode);
        self::assertSame('A1:D1', $DOMNode->getAttribute('ref'), 'Merge ref for first range is not valid.');
        $xmlReader->readUntilNodeFound('mergeCell');
        $DOMNode1 = $xmlReader->expand();
        self::assertInstanceOf(DOMElement::class, $DOMNode1);
        self::assertSame('C3:K3', $DOMNode1->getAttribute('ref'), 'Merge ref for second range is not valid.');
    }

    public function testMergeCellsOnSeparateSheets(): void
    {
        $fileName = 'test_merge_cells_on_separate_sheets.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $options->mergeCells(0, 1, 3, 1, $writer->getCurrentSheet()->getIndex());
        $writer->addNewSheetAndMakeItCurrent();
        $options->mergeCells(2, 3, 10, 3, $writer->getCurrentSheet()->getIndex());
        $writer->close();

        $sheet1 = $this->getXmlReaderForSheetFromXmlFile($fileName, '1');
        $sheet1->readUntilNodeFound('mergeCells');
        self::assertSame('mergeCells', $sheet1->getCurrentNodeName(), 'Sheet 1 does not have mergeCells tag');

        $mergeCells1 = $sheet1->expand();
        self::assertNotFalse($mergeCells1);
        self::assertSame(1, $mergeCells1->childNodes->length, 'Sheet 1 does not have the specified number of mergeCell definitions');
        $merge1 = $mergeCells1->childNodes->item(0);
        self::assertInstanceOf(DOMElement::class, $merge1);
        self::assertSame('A1:D1', $merge1->getAttribute('ref'), 'Merge ref for first range is not valid.');

        $sheet2 = $this->getXmlReaderForSheetFromXmlFile($fileName, '2');
        $sheet2->readUntilNodeFound('mergeCells');
        self::assertSame('mergeCells', $sheet2->getCurrentNodeName(), 'Sheet 2 does not have mergeCells tag');

        $mergeCells2 = $sheet2->expand();
        self::assertNotFalse($mergeCells2);
        self::assertSame(1, $mergeCells2->childNodes->length, 'Sheet 2 does not have the specified number of mergeCell definitions');
        $merge2 = $mergeCells2->childNodes->item(0);
        self::assertInstanceOf(DOMElement::class, $merge2);
        self::assertSame('C3:K3', $merge2->getAttribute('ref'), 'Merge ref for first range is not valid.');
    }

    public function testGeneratedFileShouldBeValidForEmptySheets(): void
    {
        $fileName = 'test_empty_sheet.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $writer->addNewSheetAndMakeItCurrent();
        $writer->close();

        $xmlReader = $this->getXmlReaderForSheetFromXmlFile($fileName, '1');
        $xmlReader->setParserProperty(XMLReader::VALIDATE, true);
        self::assertTrue($xmlReader->isValid(), 'worksheet xml is not valid');
        $xmlReader->setParserProperty(XMLReader::VALIDATE, false);
        $xmlReader->readUntilNodeFound('sheetData');
        self::assertSame('sheetData', $xmlReader->getCurrentNodeName(), 'worksheet xml does not have sheetData');
    }

    public function testGeneratedFileShouldHaveTheCorrectMimeType(): void
    {
        $fileName = 'test_mime_type.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $dataRows = [Row::fromValues(['foo'])];

        $this->writeToXLSXFile($dataRows, $fileName);

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        self::assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $finfo->file($resourcePath));
    }

    public function testShouldSetOptionWithGetter(): void
    {
        $options = new Options(
            DEFAULT_COLUMN_WIDTH: (float) random_int(100, 199),
            tempFolder: (new TestUsingResource())->getTempFolderPath(),
        );
        $writer = new Writer($options);

        self::assertSame($options->DEFAULT_COLUMN_WIDTH, $writer->getOptions()->DEFAULT_COLUMN_WIDTH);
    }

    public function testSheetFilenameAreStoredWithIndex(): void
    {
        $fileName = 'sheet_indexes.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->getCurrentSheet()->setName(uniqid());
        $writer->addRow(Row::fromValues(['foo']));
        $writer->close();

        $this->assertInlineDataWasWrittenToSheet($fileName, 1, 'foo');
    }

    public function testShouldReturnWrittenRowCount(): void
    {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath('row_count.xlsx');

        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        self::assertSame(0, $writer->getWrittenRowCount());
        $writer->openToFile($resourcePath);

        $firstSheet = $writer->getCurrentSheet();
        self::assertSame(0, $writer->getWrittenRowCount());
        self::assertSame(0, $firstSheet->getWrittenRowCount());
        $writer->addRow(Row::fromValues(['csv-1', null]));
        self::assertSame(1, $writer->getWrittenRowCount());
        self::assertSame(1, $firstSheet->getWrittenRowCount());
        $writer->addRow(Row::fromValues(['csv-2', null]));
        self::assertSame(2, $writer->getWrittenRowCount());
        self::assertSame(2, $firstSheet->getWrittenRowCount());
        $writer->addRows([
            Row::fromValues(['csv--11', 'csv--12']),
            Row::fromValues([]),
            Row::fromValues(['csv--31', 'csv--32']),
        ]);
        self::assertSame(5, $writer->getWrittenRowCount());

        $secondSheet = $writer->addNewSheetAndMakeItCurrent();
        self::assertSame(5, $writer->getWrittenRowCount());
        self::assertSame(5, $firstSheet->getWrittenRowCount());
        self::assertSame(0, $secondSheet->getWrittenRowCount());
        $writer->addRow(Row::fromValues(['csv-1', null]));
        self::assertSame(6, $writer->getWrittenRowCount());
        self::assertSame(5, $firstSheet->getWrittenRowCount());
        self::assertSame(1, $secondSheet->getWrittenRowCount());

        $writer->close();
        self::assertSame(6, $writer->getWrittenRowCount());
    }

    public function testCloseShouldAddDimensionTag(): void
    {
        $fileName = 'test_close_should_add_dimension_tag.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->addRow(Row::fromValues(['csv-1', null]));
        $writer->addRow(Row::fromValues(['csv-2-1', 'csv-2-2']));
        $writer->addRow(Row::fromValues([null, 'csv-3']));
        $writer->close();

        $xmlReader = $this->getXmlReaderForSheetFromXmlFile($fileName, '1');
        $xmlReader->readUntilNodeFound('dimension');
        self::assertSame('dimension', $xmlReader->getCurrentNodeName(), 'Sheet does not have dimension tag');
        $DOMNode = $xmlReader->expand();
        self::assertInstanceOf(DOMElement::class, $DOMNode);
        self::assertSame('A1:B3', $DOMNode->getAttribute('ref'), 'Merge ref for dimension range is not valid.');
    }

    public function testCloseShouldAddAutofilterTag(): void
    {
        $fileName = 'test_close_should_add_autofilter_tag.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $autoFilter = new AutoFilter(0, 1, 3, 3);
        $writer->getCurrentSheet()->setAutoFilter($autoFilter);
        $writer->close();

        $xmlReader = $this->getXmlReaderForSheetFromXmlFile($fileName, '1');
        $xmlReader->readUntilNodeFound('autoFilter');
        self::assertSame('autoFilter', $xmlReader->getCurrentNodeName(), 'Sheet does not have autoFilter tag');
        $DOMNode = $xmlReader->expand();
        self::assertInstanceOf(DOMElement::class, $DOMNode);
        self::assertSame('A1:D3', $DOMNode->getAttribute('ref'), 'Merge ref for autoFilter range is not valid.');
    }

    public function testRemoveAutofilterShouldDeleteAllAutofilterTag(): void
    {
        $fileName = 'test_remove_autofilter_should_delete_all_autofilter_tag.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $autoFilter = new AutoFilter(0, 1, 3, 3);
        $writer->getCurrentSheet()->setAutoFilter($autoFilter);
        $writer->close();

        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->getCurrentSheet()->setAutoFilter(null);
        $writer->close();

        $xmlReader = new XMLReader();
        $xmlReader->openFileInZip($resourcePath, 'xl/worksheets/sheet1.xml');
        $foundAutofilterTag = $xmlReader->readUntilNodeFound('autoFilter');
        self::assertFalse($foundAutofilterTag);
        $xmlReader->openFileInZip($resourcePath, 'xl/workbook.xml');
        $foundDefinedNamesTag = $xmlReader->readUntilNodeFound('definedNames');
        self::assertFalse($foundDefinedNamesTag);
    }

    public function testAddAutofilterToTwoSheetsShouldWriteCorrectDataToWorkbookFile(): void
    {
        $fileName = 'test_add_autofilter_to_two_sheets_should-write-correct-data-to-workbook-file.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->getCurrentSheet()->setName('Sheet First');
        $autoFilter1 = new AutoFilter(0, 1, 3, 3);
        $writer->getCurrentSheet()->setAutoFilter($autoFilter1);
        $writer->addNewSheetAndMakeItCurrent();
        $writer->getCurrentSheet()->setName('Sheet Last');
        $autoFilter2 = new AutoFilter(0, 1, 26, 11);
        $writer->getCurrentSheet()->setAutoFilter($autoFilter2);
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#xl/workbook.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        self::assertNotFalse($xmlContents);

        $xmlReader = new XMLReader();
        $xmlReader->openFileInZip($resourcePath, 'xl/workbook.xml');
        $xmlReader->readUntilNodeFound('definedNames');
        self::assertSame('definedNames', $xmlReader->getCurrentNodeName(), 'Workbook does not have definedNames tag');

        /** @var DOMElement $DOMNode */
        $DOMNode = $xmlReader->expand();
        self::assertSame(2, $DOMNode->childElementCount, 'Workbook does not have the specified number of definedName tags');

        /** @var DOMElement $firstFilter */
        $firstFilter = $DOMNode->childNodes->item(0);
        self::assertSame('\'Sheet First\'!$A$1:$D$3', $firstFilter->nodeValue, 'DefinedName is not valid.');
        self::assertSame('0', $firstFilter->getAttribute('localSheetId'), 'Sheet Id is not valid.');

        /** @var DOMElement $secondFilter */
        $secondFilter = $DOMNode->childNodes->item(1);
        self::assertSame('\'Sheet Last\'!$A$1:$AA$11', $secondFilter->nodeValue, 'DefinedName is not valid.');
        self::assertSame('1', $secondFilter->getAttribute('localSheetId'), 'Sheet Id is not valid.');
    }

    public function testAddCommentShouldBeWrittenToTwoFiles(): void
    {
        $fileName = 'test_add_comment_should_be_written_to_two_files.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $textRun = new TextRun(
            text: 'Great comment',
            fontSize: 12,
            fontColor: 'FF0000',
            fontName: 'Arial',
            bold: true,
            italic: false,
        );

        $comment = new Comment(
            height: '200px',
            width: '400px',
            marginLeft: '59.25pt',
            marginTop: '1.5pt',
            visible: false,
            fillColor: '#F0F0F0',
            textRuns: [$textRun],
        );

        $cell = Cell::fromValue('Test', null, $comment);
        $row = new Row([Cell::fromValue('something'), $cell, Cell::fromValue('else')]);
        $writer->addRow($row);
        $writer->close();

        // Now test if the resources contain what we need
        $pathToCommentFile = $resourcePath.'#xl/comments1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToCommentFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('Great comment', $xmlContents, '');
        self::assertStringContainsString('<b/>', $xmlContents, '');
        self::assertStringContainsString('<sz val="12"/>', $xmlContents, '');
        self::assertStringContainsString('<color rgb="FF0000"/>', $xmlContents, '');
        self::assertStringContainsString('<rFont val="Arial"/>', $xmlContents, '');

        $pathToVmlFile = $resourcePath.'#xl/drawings/vmlDrawing1.vml';
        $vmlContents = file_get_contents('zip://'.$pathToVmlFile);

        self::assertNotFalse($vmlContents);
        self::assertStringContainsString('<x:Row>0</x:Row>', $vmlContents, '');
        self::assertStringContainsString('<x:Column>1</x:Column>', $vmlContents, '');
        self::assertStringContainsString('width:400px', $vmlContents, '');
        self::assertStringContainsString('height:200px', $vmlContents, '');
    }

    public function testAddCommentBoldNotItalic(): void
    {
        $fileName = 'test_add_comment_bold_not_italic.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $textRun = new TextRun(
            text: 'Great comment',
            fontSize: 12,
            fontColor: 'FF0000',
            fontName: 'Arial',
            bold: true,
            italic: false,
        );

        $comment = new Comment(textRuns: [$textRun]);

        $cell = Cell::fromValue('Test', null, $comment);
        $row = new Row([Cell::fromValue('something'), $cell, Cell::fromValue('else')]);
        $writer->addRow($row);
        $writer->close();

        // Now test if the resources contain what we need
        $pathToCommentFile = $resourcePath.'#xl/comments1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToCommentFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('Great comment', $xmlContents, '');
        self::assertStringContainsString('<b/>', $xmlContents, '');
        self::assertStringNotContainsString('<i/>', $xmlContents, '');
    }

    public function testAddCommentItalicNotBold(): void
    {
        $fileName = 'test_add_comment_italic_not_bold.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $textRun = new TextRun(
            text: 'Great comment',
            fontSize: 12,
            fontColor: 'FF0000',
            fontName: 'Arial',
            bold: false,
            italic: true,
        );

        $comment = new Comment(textRuns: [$textRun]);

        $cell = Cell::fromValue('Test', null, $comment);
        $row = new Row([Cell::fromValue('something'), $cell, Cell::fromValue('else')]);
        $writer->addRow($row);
        $writer->close();

        // Now test if the resources contain what we need
        $pathToCommentFile = $resourcePath.'#xl/comments1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToCommentFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('Great comment', $xmlContents, '');
        self::assertStringContainsString('<i/>', $xmlContents, '');
        self::assertStringNotContainsString('<b/>', $xmlContents, '');
    }

    public function testAddPageSetup(): void
    {
        $fileName = 'test_page_setup.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $options = new Options(
            tempFolder: (new TestUsingResource())->getTempFolderPath(),
            pageMargin: new PageMargin(1, 2, 3, 4, 5, 6),
            pageSetup: new PageSetup(
                PageOrientation::LANDSCAPE,
                PaperSize::A4,
            ),
        );

        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $row = new Row([Cell::fromValue('something'), Cell::fromValue('else')]);
        $writer->addRow($row);
        $writer->close();

        // Now test if the resources contain what we need
        $pathToSheetFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToSheetFile);

        self::assertNotFalse($xmlContents);
        self::assertStringNotContainsString('<sheetPr><pageSetUpPr fitToPage="true"/></sheetPr>', $xmlContents);
        self::assertStringContainsString('<pageMargins top="1" right="2" bottom="3" left="4" header="5" footer="6"/>', $xmlContents);
    }

    public function testAddfitToPageWithTwoArgs(): void
    {
        $fileName = 'test_fit_to_page.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $options = new Options(
            tempFolder: (new TestUsingResource())->getTempFolderPath(),
            pageSetup: new PageSetup(
                PageOrientation::LANDSCAPE,
                PaperSize::A4,
                0,
                1
            ),
        );

        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $row = new Row([Cell::fromValue('something'), Cell::fromValue('else')]);
        $writer->addRow($row);
        $writer->close();

        // Now test if the resources contain what we need
        $pathToSheetFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToSheetFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('<sheetPr><pageSetUpPr fitToPage="true"/></sheetPr>', $xmlContents);
        self::assertStringContainsString('<pageSetup orientation="landscape" paperSize="9" fitToHeight="0" fitToWidth="1"/>', $xmlContents);
    }

    public function testAddfitToPageWithOneArgs(): void
    {
        $fileName = 'test_fit_to_page.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $options = new Options(
            tempFolder: (new TestUsingResource())->getTempFolderPath(),
            pageSetup: new PageSetup(
                PageOrientation::LANDSCAPE,
                PaperSize::A4,
                1,
            ),
        );

        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $row = new Row([Cell::fromValue('something'), Cell::fromValue('else')]);
        $writer->addRow($row);
        $writer->close();

        // Now test if the resources contain what we need
        $pathToSheetFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToSheetFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('<sheetPr><pageSetUpPr fitToPage="true"/></sheetPr>', $xmlContents);
        self::assertStringContainsString('<pageSetup orientation="landscape" paperSize="9" fitToHeight="1"/>', $xmlContents);
    }

    public function testAddAutoFilterShouldWriteCorrectSheetPr(): void
    {
        $fileName = 'test_auto_filter_should_write_correct_sheet_pr.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());

        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $sheet = $writer->getCurrentSheet();
        $autoFilter = new AutoFilter(0, 1, 3, 3);
        $sheet->setAutoFilter($autoFilter);

        $row = new Row([Cell::fromValue('something'), Cell::fromValue('else')]);
        $writer->addRow($row);
        $writer->close();

        // Now test if the resources contain what we need
        $pathToSheetFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToSheetFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('<sheetPr filterMode="false"><pageSetUpPr fitToPage="false"/></sheetPr>', $xmlContents);
    }

    public function testAddAutoFilterAndfitToPageShouldWriteCorrectSheetPr(): void
    {
        $fileName = 'test_auto_filter_fit_to_page_should_write_correct_sheet_pr.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $options = new Options(
            tempFolder: (new TestUsingResource())->getTempFolderPath(),
            pageSetup: new PageSetup(
                PageOrientation::LANDSCAPE,
                PaperSize::A4,
                1,
            ),
        );

        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $sheet = $writer->getCurrentSheet();
        $autoFilter = new AutoFilter(0, 1, 3, 3);
        $sheet->setAutoFilter($autoFilter);

        $row = new Row([Cell::fromValue('something'), Cell::fromValue('else')]);
        $writer->addRow($row);
        $writer->close();

        // Now test if the resources contain what we need
        $pathToSheetFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToSheetFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('<sheetPr filterMode="false"><pageSetUpPr fitToPage="true"/></sheetPr>', $xmlContents);
    }

    public function testAddHeaderFooterDifferentOddEven(): void
    {
        $fileName = 'test_header_footer.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $options = new Options(
            tempFolder: (new TestUsingResource())->getTempFolderPath(),
            headerFooter: new HeaderFooter(
                'oddHeader',
                'oddFooter',
                'evenHeader',
                'evenFooter',
                true
            ),
        );

        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $row = new Row([Cell::fromValue('something'), Cell::fromValue('else')]);
        $writer->addRow($row);
        $writer->close();

        // Now test if the resources contain what we need
        $pathToSheetFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToSheetFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString(
            '<headerFooter differentOddEven="1">'
            .'<oddHeader>oddHeader</oddHeader>'
            .'<oddFooter>oddFooter</oddFooter>'
            .'<evenHeader>evenHeader</evenHeader>'
            .'<evenFooter>evenFooter</evenFooter>'
            .'</headerFooter>',
            $xmlContents
        );
    }

    public function testAddHeaderFooterDifferentOddEvenWithoutArgument(): void
    {
        $fileName = 'test_header_footer.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $options = new Options(
            tempFolder: (new TestUsingResource())->getTempFolderPath(),
            headerFooter: new HeaderFooter(
                'oddHeader',
                'oddFooter',
                'evenHeader',
                'evenFooter',
            ),
        );

        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $row = new Row([Cell::fromValue('something'), Cell::fromValue('else')]);
        $writer->addRow($row);
        $writer->close();

        // Now test if the resources contain what we need
        $pathToSheetFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToSheetFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString(
            '<headerFooter>'
            .'<oddHeader>oddHeader</oddHeader>'
            .'<oddFooter>oddFooter</oddFooter>'
            .'</headerFooter>',
            $xmlContents
        );
    }

    public function testAddHeaderFooterSameOddEven(): void
    {
        $fileName = 'test_header_footer.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $options = new Options(
            tempFolder: (new TestUsingResource())->getTempFolderPath(),
            headerFooter: new HeaderFooter(
                'oddHeader',
                'oddFooter',
                'evenHeader',
                null,
                false
            ),
        );

        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $row = new Row([Cell::fromValue('something'), Cell::fromValue('else')]);
        $writer->addRow($row);
        $writer->close();

        // Now test if the resources contain what we need
        $pathToSheetFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToSheetFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString(
            '<headerFooter>'
            .'<oddHeader>oddHeader</oddHeader>'
            .'<oddFooter>oddFooter</oddFooter>'
            .'</headerFooter>',
            $xmlContents
        );
    }

    public function testAddPrintTitleRows(): void
    {
        $fileName = 'test_print_title_rows.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());

        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $sheet = $writer->getCurrentSheet();
        $sheet->setPrintTitleRows('$1:$1');
        $writer->close();

        // Now test if the resources contain what we need
        $pathToBookFile = $resourcePath.'#xl/workbook.xml';
        $xmlContents = file_get_contents('zip://'.$pathToBookFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString(
            '<definedNames><definedName name="_xlnm.Print_Titles" localSheetId="0">Sheet1!$1:$1</definedName></definedNames>',
            $xmlContents
        );
    }

    public function testAddPrintTitleRowsNotOverwriteOtherDefinedName(): void
    {
        $fileName = 'test_print_title_rows_not_overwrite_other_defined_name.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());

        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $sheet = $writer->getCurrentSheet();
        $autoFilter = new AutoFilter(0, 1, 3, 3);
        $sheet->setAutoFilter($autoFilter);
        $sheet->setPrintTitleRows('$1:$1');
        $writer->close();

        // Now test if the resources contain what we need
        $pathToBookFile = $resourcePath.'#xl/workbook.xml';
        $xmlContents = file_get_contents('zip://'.$pathToBookFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString(
            '<definedName function="false" hidden="true" localSheetId="0" name="_xlnm._FilterDatabase" vbProcedure="false">',
            $xmlContents
        );
        self::assertStringContainsString(
            '<definedName name="_xlnm.Print_Titles" localSheetId="0">Sheet1!$1:$1</definedName>',
            $xmlContents
        );
    }

    public function testWriteDateInterval(): void
    {
        $fileName = 'test_date_interval.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());

        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $row = new Row([new Cell\DateIntervalCell(DateInterval::createFromDateString('36 hours'), null)]);
        $writer->addRow($row);
        $writer->close();

        // Now test if the resources contain what we need
        $pathToSheetFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToSheetFile);

        self::assertNotFalse($xmlContents);
        $xml = new DOMDocument();
        self::assertTrue($xml->loadXML($xmlContents), 'Sheet is valid XML');

        self::assertStringContainsString('<v>1.5</v>', $xmlContents, '36 hours are 1.5 days');
    }

    public function testAddSheetProtection(): void
    {
        $fileName = 'test_sheet_protection_setup.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());

        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $protection = new SheetProtection(
            password: 'password',
            lockSheet: true,
            lockColumnInsert: false,
            lockColumnDelete: true,
            lockColumnFormatting: false,
            lockRowInsert: true,
            lockRowDelete: false,
            lockRowFormatting: true,
            lockAutoFilter: false,
            lockSort: true,
            lockCellFormatting: false,
            lockLockedCellSelection: true,
            lockUnlockedCellsSelection: false,
            lockObjects: true,
            lockHyperlinkInsert: false,
            lockPivotTables: true,
            lockScenarios: false,
        );

        $writer->getCurrentSheet()
            ->setSheetProtection($protection)
        ;

        $row = new Row([Cell::fromValue('something'), Cell::fromValue('else')]);
        $writer->addRow($row);
        $writer->close();

        // Now test if the resources contain what we need
        $pathToSheetFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToSheetFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('<sheetProtection password="83AF" sheet="true" objects="true" scenarios="false" formatCells="false" formatColumns="false" formatRows="true" insertColumns="false" insertRows="true" deleteColumns="true" deleteRows="false" selectLockedCells="true" selectUnlockedCells="false" autoFilter="false" sort="true" hyperlink="false" pivotTables="true"/>', $xmlContents);
    }

    public function testSheetProtectionElementIsCorrectlyPositioned(): void
    {
        $fileName = 'test_sheet_protection_setup.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());

        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $protection = new SheetProtection(
            password: 'password',
        );

        $writer->getCurrentSheet()->setSheetProtection($protection);

        $row = new Row([Cell::fromValue('something'), Cell::fromValue('else')]);
        $writer->addRow($row);
        $writer->close();

        // Now test if the resources contain what we need
        $pathToSheetFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToSheetFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('</sheetData><sheetProtection ', $xmlContents);
    }

    public function testSetWorkbookProtection(): void
    {
        $fileName = 'test_set_workbook_protection.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(
            tempFolder: (new TestUsingResource())->getTempFolderPath(),
            workbookProtection: new WorkbookProtection(
                password: 'password',
                lockStructure: true,
                lockWindows: true,
                lockRevisions: true,
            ),
        );

        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->close();

        // Now test if the resources contain what we need
        $pathToSheetFile = $resourcePath.'#xl/workbook.xml';
        $xmlContents = file_get_contents('zip://'.$pathToSheetFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('<workbookProtection workbookPassword="83AF" lockStructure="true" lockWindows="true" lockRevisions="true"/>', $xmlContents);
    }

    public function testWriteValidationTagsToXml(): void
    {
        $fileName = 'test_add_data_validation.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->addRow(Row::fromValues(['Name', 'Score']));

        $sheetIndex = $writer->getCurrentSheet()->getIndex();
        $options->mergeCells(0, 1, 0, 2, $sheetIndex);
        $options->addValidation(
            topLeftColumn: 1,
            topLeftRow: 2,
            bottomRightColumn: 1,
            bottomRightRow: 100,
            rule: new WholeNumberValidationRule(ValidationOperator::Between, 0, 100),
            validation_display: new ValidationDisplay(
                allowBlank: false,
                errorStyle: ErrorStyle::Warning,
                promptTitle: 'Enter a score',
                errorTitle: 'Invalid value',
            ),
            sheetIndex: $sheetIndex,
        );

        $writer->close();

        $xmlContents = file_get_contents('zip://'.$resourcePath.'#xl/worksheets/sheet1.xml');
        self::assertNotFalse($xmlContents);

        self::assertStringContainsString('type="whole"', $xmlContents);
        self::assertStringContainsString('operator="between"', $xmlContents);
        self::assertStringContainsString('sqref="B2:B100"', $xmlContents);
        self::assertStringContainsString('<formula1>0</formula1>', $xmlContents);
        self::assertStringContainsString('<formula2>100</formula2>', $xmlContents);

        self::assertStringContainsString('allowBlank="0"', $xmlContents);
        self::assertStringContainsString('errorStyle="warning"', $xmlContents);
        self::assertStringContainsString('promptTitle="Enter a score"', $xmlContents);
        self::assertStringContainsString('errorTitle="Invalid value"', $xmlContents);

        self::assertGreaterThan(strpos($xmlContents, '<mergeCells'), strpos($xmlContents, '<dataValidations'));
    }

    public function testDataValidationsOnSeparateSheets(): void
    {
        $fileName = 'test_data_validations_on_separate_sheets.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->addRow(Row::fromValues(['Name', 'Status']));

        $sheet1Index = $writer->getCurrentSheet()->getIndex();
        $options->addValidation(
            topLeftColumn: 1,
            topLeftRow: 2,
            bottomRightColumn: 1,
            bottomRightRow: 100,
            rule: new ListValidationRule(new CellReference(0, 2, 0, 10)),
            sheetIndex: $sheet1Index,
        );

        $writer->addNewSheetAndMakeItCurrent();
        $writer->addRow(Row::fromValues(['Product', 'Category']));

        $sheet2Index = $writer->getCurrentSheet()->getIndex();
        $options->addValidation(
            topLeftColumn: 1,
            topLeftRow: 2,
            bottomRightColumn: 1,
            bottomRightRow: 100,
            rule: new ListValidationRule(new CellReference(1, 2, 1, 10)),
            sheetIndex: $sheet2Index,
        );

        $writer->close();

        $sheet1XmlContents = file_get_contents('zip://'.$resourcePath.'#xl/worksheets/sheet1.xml');
        self::assertNotFalse($sheet1XmlContents);
        self::assertStringContainsString('<dataValidations', $sheet1XmlContents, 'Sheet 1 should have data validations');
        self::assertStringContainsString('type="list"', $sheet1XmlContents, 'Sheet 1 should have a list validation');
        self::assertStringContainsString('$A$2:$A$10', $sheet1XmlContents, 'Sheet 1 should reference column A');
        self::assertStringNotContainsString('$B$2:$B$10"', $sheet1XmlContents, 'Sheet 1 should not reference sheet 2 range');

        $sheet2XmlContents = file_get_contents('zip://'.$resourcePath.'#xl/worksheets/sheet2.xml');
        self::assertNotFalse($sheet2XmlContents);
        self::assertStringContainsString('<dataValidations', $sheet2XmlContents, 'Sheet 2 should have data validations');
        self::assertStringContainsString('type="list"', $sheet2XmlContents, 'Sheet 2 should have a list validation');
        self::assertStringContainsString('$B$2:$B$10', $sheet2XmlContents, 'Sheet 2 should reference column B');
        self::assertStringNotContainsString('$A$2:$A$10"', $sheet2XmlContents, 'Sheet 2 should not reference sheet 1 range');
    }

    public function testWriteImageCellEmbedsDrawingFiles(): void
    {
        $fileName = 'test_write_image_cell.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->addRow(new Row([new Cell\ImageCell($this->testImagePath, 1, 1)]));
        $writer->close();

        $sheetXml = file_get_contents('zip://'.$resourcePath.'#xl/worksheets/sheet1.xml');
        self::assertNotFalse($sheetXml);
        self::assertStringContainsString('<drawing r:id="rIdDrawing1"', $sheetXml);

        $drawingXml = file_get_contents('zip://'.$resourcePath.'#xl/drawings/drawing1.xml');
        self::assertNotFalse($drawingXml, 'Drawing XML file should exist in the archive');
        self::assertStringContainsString('<xdr:oneCellAnchor>', $drawingXml);
        self::assertStringContainsString('r:embed="rId1"', $drawingXml);

        $drawingRelsXml = file_get_contents('zip://'.$resourcePath.'#xl/drawings/_rels/drawing1.xml.rels');
        self::assertNotFalse($drawingRelsXml, 'Drawing rels file should exist in the archive');
        self::assertStringContainsString('relationships/image', $drawingRelsXml);
        self::assertStringContainsString('image1.png', $drawingRelsXml);

        $contentTypes = file_get_contents('zip://'.$resourcePath.'#[Content_Types].xml');
        self::assertNotFalse($contentTypes);
        self::assertStringContainsString('drawing+xml', $contentTypes);
        self::assertStringContainsString('image/png', $contentTypes);

        $sheetRels = file_get_contents('zip://'.$resourcePath.'#xl/worksheets/_rels/sheet1.xml.rels');
        self::assertNotFalse($sheetRels);
        self::assertStringContainsString('rIdDrawing1', $sheetRels);
        self::assertStringContainsString('drawings/drawing1.xml', $sheetRels);
    }

    public function testDuplicateImageCellIsEmbeddedOnlyOnce(): void
    {
        $fileName = 'test_image_cell_dedup.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        // Add the same image in two different cells
        $writer->addRow(new Row([new Cell\ImageCell($this->testImagePath, 1, 1)]));
        $writer->addRow(new Row([new Cell\ImageCell($this->testImagePath, 1, 1)]));
        $writer->close();

        // The drawing should contain two anchors (one per cell)
        $drawingXml = file_get_contents('zip://'.$resourcePath.'#xl/drawings/drawing1.xml');
        self::assertNotFalse($drawingXml);
        self::assertSame(2, substr_count($drawingXml, '<xdr:oneCellAnchor>'), 'There should be two drawing anchors');
        // Both anchors must reference the same relationship
        self::assertSame(2, substr_count($drawingXml, 'r:embed="rId1"'), 'Both anchors should reference rId1');

        // The drawing rels file must contain exactly one image relationship
        $drawingRelsXml = file_get_contents('zip://'.$resourcePath.'#xl/drawings/_rels/drawing1.xml.rels');
        self::assertNotFalse($drawingRelsXml);
        self::assertSame(1, substr_count($drawingRelsXml, 'relationships/image'), 'The image should only be referenced once in drawing rels');
        self::assertStringContainsString('image1.png', $drawingRelsXml);

        // The archive must contain the media file exactly once
        $zip = new ZipArchive();
        $zip->open($resourcePath);
        $mediaCount = 0;
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $name = $zip->getNameIndex($i);
            if (false !== $name && str_starts_with($name, 'xl/media/')) {
                ++$mediaCount;
            }
        }
        $zip->close();
        self::assertSame(1, $mediaCount, 'The same image should be stored only once in the archive');
    }

    public function testAddNewSheetAndMakeItCurrentLeaksFileHandles(): void
    {
        if (!is_dir('/proc/self/fd')) {
            self::markTestSkipped('This test requires Linux with /proc/self/fd');
        }

        $fileName = 'test_sheet_file_handle_leak.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $countFds = static function (): int {
            $fds = scandir('/proc/self/fd');
            \assert(false !== $fds);

            return \count($fds) - 2; // exclude . and ..
        };

        $fdsAfterOpen = $countFds();

        $numSheets = 10;
        for ($i = 0; $i < $numSheets; ++$i) {
            $writer->addNewSheetAndMakeItCurrent();
        }

        $fdsAfterAdd = $countFds();
        $writer->close();
        $fdsAfterClose = $countFds();

        // Each additional sheet opens 3 file handles (sheet XML, comments XML, drawing VML)
        // that remain open until close(). The first sheet's handles are already in $fdsAfterOpen.
        $minimumExpectedIncrease = ($numSheets - 1) * 3;
        $actualIncrease = $fdsAfterAdd - $fdsAfterOpen;

        self::assertLessThan(
            $minimumExpectedIncrease,
            $actualIncrease,
            \sprintf(
                'Expected less than %d additional file handles for %d sheets, but got %d. '
                .'This confirms file handles are recycled (close before switch).',
                $minimumExpectedIncrease,
                $numSheets,
                $actualIncrease,
            )
        );

        // After close(), handles must return to near-initial level
        self::assertLessThanOrEqual(
            $fdsAfterOpen + 2,
            $fdsAfterClose,
            'Writer::close() should release all accumulated file handles'
        );
    }

    /**
     * @param Row[] $allRows
     */
    private function writeToXLSXFile(
        array $allRows,
        string $fileName,
        ?bool $shouldUseInlineStrings = null,
        ?bool $shouldCreateSheetsAutomatically = null
    ): Writer {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = ['tempFolder' => (new TestUsingResource())->getTempFolderPath()];
        if (null !== $shouldUseInlineStrings) {
            $options['SHOULD_USE_INLINE_STRINGS'] = $shouldUseInlineStrings;
        }
        if (null !== $shouldCreateSheetsAutomatically) {
            $options['SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY'] = $shouldCreateSheetsAutomatically;
        }
        $writer = new Writer(new Options(...$options));

        $writer->openToFile($resourcePath);
        $writer->addRows($allRows);
        $writer->close();

        return $writer;
    }

    /**
     * @param Row[] $allRows
     */
    private function writeToMultipleSheetsInXLSXFile(
        array $allRows,
        int $numSheets,
        string $fileName,
        ?bool $shouldUseInlineStrings = null,
        ?bool $shouldCreateSheetsAutomatically = null
    ): Writer {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = ['tempFolder' => (new TestUsingResource())->getTempFolderPath()];
        if (null !== $shouldUseInlineStrings) {
            $options['SHOULD_USE_INLINE_STRINGS'] = $shouldUseInlineStrings;
        }
        if (null !== $shouldCreateSheetsAutomatically) {
            $options['SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY'] = $shouldCreateSheetsAutomatically;
        }
        $writer = new Writer(new Options(...$options));

        $writer->openToFile($resourcePath);
        $writer->addRows($allRows);

        for ($i = 1; $i < $numSheets; ++$i) {
            $writer->addNewSheetAndMakeItCurrent();
            $writer->addRows($allRows);
        }

        $writer->close();

        return $writer;
    }

    /**
     * @param mixed $inlineData
     */
    private function assertInlineDataWasWrittenToSheet(string $fileName, int $sheetIndex, $inlineData, string $message = ''): void
    {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $pathToSheetFile = $resourcePath.'#xl/worksheets/sheet'.$sheetIndex.'.xml';
        $xmlContents = file_get_contents('zip://'.$pathToSheetFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString((string) $inlineData, $xmlContents, $message);
    }

    /**
     * @param mixed $inlineData
     */
    private function assertInlineDataWasNotWrittenToSheet(string $fileName, int $sheetIndex, $inlineData, string $message = ''): void
    {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $pathToSheetFile = $resourcePath.'#xl/worksheets/sheet'.$sheetIndex.'.xml';
        $xmlContents = file_get_contents('zip://'.$pathToSheetFile);

        self::assertNotFalse($xmlContents);
        self::assertStringNotContainsString((string) $inlineData, $xmlContents, $message);
    }

    private function assertSharedStringWasWritten(string $fileName, string $sharedString, string $message = ''): void
    {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $pathToSharedStringsFile = $resourcePath.'#xl/sharedStrings.xml';
        $xmlContents = file_get_contents('zip://'.$pathToSharedStringsFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString($sharedString, $xmlContents, $message);
    }

    /**
     * @param string $sheetIndex - 1 based
     */
    private function getXmlReaderForSheetFromXmlFile(string $fileName, string $sheetIndex): XMLReader
    {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $xmlReader = new XMLReader();
        $xmlReader->openFileInZip($resourcePath, 'xl/worksheets/sheet'.$sheetIndex.'.xml');

        return $xmlReader;
    }
}
