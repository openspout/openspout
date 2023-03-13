<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

use OpenSpout\Common\Entity\Row;
use OpenSpout\TestUsingResource;
use OpenSpout\Writer\Common\Entity\Sheet;
use OpenSpout\Writer\Exception\InvalidSheetNameException;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use OpenSpout\Writer\RowCreationHelper;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SheetTest extends TestCase
{
    use RowCreationHelper;

    public function testGetSheetIndex(): void
    {
        $sheets = $this->writeDataToMultipleSheetsAndReturnSheets('test_get_sheet_index.xlsx');

        self::assertCount(2, $sheets, '2 sheets should have been created');
        self::assertSame(0, $sheets[0]->getIndex(), 'The first sheet should be index 0');
        self::assertSame(1, $sheets[1]->getIndex(), 'The second sheet should be index 1');
    }

    public function testGetSheetName(): void
    {
        $sheets = $this->writeDataToMultipleSheetsAndReturnSheets('test_get_sheet_name.xlsx');

        self::assertCount(2, $sheets, '2 sheets should have been created');
        self::assertSame('Sheet1', $sheets[0]->getName(), 'Invalid name for the first sheet');
        self::assertSame('Sheet2', $sheets[1]->getName(), 'Invalid name for the second sheet');
    }

    public function testSetSheetNameShouldCreateSheetWithCustomName(): void
    {
        $fileName = 'test_set_name_should_create_sheet_with_custom_name.xlsx';
        $customSheetName = 'CustomName';
        $this->writeDataToSheetWithCustomName($fileName, $customSheetName);

        $this->assertSheetNameEquals($customSheetName, $fileName, "The sheet name should have been changed to '{$customSheetName}'");
    }

    public function testSetSheetNameShouldThrowWhenNameIsAlreadyUsed(): void
    {
        $this->expectException(InvalidSheetNameException::class);

        $fileName = 'test_set_name_with_non_unique_name.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $customSheetName = 'Sheet name';

        $sheet = $writer->getCurrentSheet();
        $sheet->setName($customSheetName);

        $writer->addNewSheetAndMakeItCurrent();
        $sheet = $writer->getCurrentSheet();
        $sheet->setName($customSheetName);
    }

    public function testSetSheetVisibilityShouldCreateSheetHidden(): void
    {
        $fileName = 'test_set_visibility_should_create_sheet_hidden.xlsx';
        $this->writeDataToHiddenSheet($fileName);

        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $pathToWorkbookFile = $resourcePath.'#xl/workbook.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString(' state="hidden"', $xmlContents, 'The sheet visibility should have been changed to "hidden"');
    }

    public function testThrowsIfWorkbookIsNotInitialized(): void
    {
        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $this->expectException(WriterNotOpenedException::class);

        $writer->addRow(Row::fromValues([]));
    }

    public function testWritesDefaultCellSizesIfSet(): void
    {
        $fileName = 'test_writes_default_cell_sizes_if_set.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $options->DEFAULT_COLUMN_WIDTH = 10.0;
        $options->DEFAULT_ROW_HEIGHT = 20.0;
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->addRow(Row::fromValues(['xlsx--11', 'xlsx--12']));
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('<sheetFormatPr', $xmlContents, 'No sheetFormatPr tag found in sheet');
        self::assertStringContainsString(' defaultColWidth="10', $xmlContents, 'No default column width found in sheet');
        self::assertStringContainsString(' defaultRowHeight="20', $xmlContents, 'No default row height found in sheet');
        self::assertStringContainsString(' customHeight="1"', $xmlContents, 'No row height override flag found in row');
    }

    public function testWritesDefaultRequiredRowHeightIfOmitted(): void
    {
        $fileName = 'test_writes_default_required_row_height_if_omitted.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $options->DEFAULT_COLUMN_WIDTH = 10.0;
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $writer->addRow(Row::fromValues(['xlsx--11', 'xlsx--12']));
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('<sheetFormatPr', $xmlContents, 'No sheetFormatPr tag found in sheet');
        self::assertStringContainsString(' defaultColWidth="10', $xmlContents, 'No default column width found in sheet');
        self::assertStringContainsString(' defaultRowHeight="0', $xmlContents, 'No default row height found in sheet');
    }

    public function testWritesColumnWidths(): void
    {
        $fileName = 'test_column_widths.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->addRow(Row::fromValues(['xlsx--11', 'xlsx--12']));
        $options->setColumnWidth(100.0, 1);
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('<cols', $xmlContents, 'No cols tag found in sheet');
        self::assertStringContainsString('<col min="1" max="1" width="100" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
    }

    public function testWritesMultipleColumnWidths(): void
    {
        $fileName = 'test_multiple_column_widths.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->addRow(Row::fromValues(['xlsx--11', 'xlsx--12', 'xlsx--13']));
        $options->setColumnWidth(100.0, 1, 2, 3);
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('<cols', $xmlContents, 'No cols tag found in sheet');
        self::assertStringContainsString('<col min="1" max="3" width="100" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
    }

    public function testWritesMultipleColumnWidthsInRanges(): void
    {
        $fileName = 'test_multiple_column_widths_in_ranges.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->addRow(Row::fromValues(['xlsx--11', 'xlsx--12', 'xlsx--13', 'xlsx--14', 'xlsx--15', 'xlsx--16']));
        $options->setColumnWidth(50.0, 1, 3, 4, 6);
        $options->setColumnWidth(100.0, 2, 5);
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('<cols', $xmlContents, 'No cols tag found in sheet');
        self::assertStringContainsString('<col min="1" max="1" width="50" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
        self::assertStringContainsString('<col min="3" max="4" width="50" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
        self::assertStringContainsString('<col min="6" max="6" width="50" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
        self::assertStringContainsString('<col min="2" max="2" width="100" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
        self::assertStringContainsString('<col min="5" max="5" width="100" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
    }

    public function testCanTakeColumnWidthsAsRange(): void
    {
        $fileName = 'test_column_widths_as_ranges.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->addRow(Row::fromValues(['xlsx--11', 'xlsx--12', 'xlsx--13']));
        $options->setColumnWidthForRange(50.0, 1, 3);
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('<cols', $xmlContents, 'No cols tag found in sheet');
        self::assertStringContainsString('<col min="1" max="3" width="50" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
    }

    public function testWritesColumnWidthsToSheet(): void
    {
        $fileName = 'test_column_widths.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->addRow(Row::fromValues(['xlsx--11', 'xlsx--12']));
        $sheet = $writer->getCurrentSheet();
        $sheet->setColumnWidth(100.0, 1);
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('<cols', $xmlContents, 'No cols tag found in sheet');
        self::assertStringContainsString('<col min="1" max="1" width="100" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
    }

    public function testWritesMultipleColumnWidthsToSheet(): void
    {
        $fileName = 'test_multiple_column_widths.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->addRow(Row::fromValues(['xlsx--11', 'xlsx--12', 'xlsx--13']));
        $sheet = $writer->getCurrentSheet();
        $sheet->setColumnWidth(100.0, 1, 2, 3);
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('<cols', $xmlContents, 'No cols tag found in sheet');
        self::assertStringContainsString('<col min="1" max="3" width="100" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
    }

    public function testWritesMultipleColumnWidthsInRangesToSheet(): void
    {
        $fileName = 'test_multiple_column_widths_in_ranges.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->addRow(Row::fromValues(['xlsx--11', 'xlsx--12', 'xlsx--13', 'xlsx--14', 'xlsx--15', 'xlsx--16']));
        $sheet = $writer->getCurrentSheet();
        $sheet->setColumnWidth(50.0, 1, 3, 4, 6);
        $sheet->setColumnWidth(100.0, 2, 5);
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('<cols', $xmlContents, 'No cols tag found in sheet');
        self::assertStringContainsString('<col min="1" max="1" width="50" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
        self::assertStringContainsString('<col min="3" max="4" width="50" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
        self::assertStringContainsString('<col min="6" max="6" width="50" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
        self::assertStringContainsString('<col min="2" max="2" width="100" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
        self::assertStringContainsString('<col min="5" max="5" width="100" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
    }

    public function testCanTakeColumnWidthsAsRangeToSheet(): void
    {
        $fileName = 'test_column_widths_as_ranges.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->addRow(Row::fromValues(['xlsx--11', 'xlsx--12', 'xlsx--13']));
        $sheet = $writer->getCurrentSheet();
        $sheet->setColumnWidthForRange(50.0, 1, 3);
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('<cols', $xmlContents, 'No cols tag found in sheet');
        self::assertStringContainsString('<col min="1" max="3" width="50" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
    }

    public function testWritesColumnWidthsToSheetOverridingOptions(): void
    {
        $fileName = 'test_column_widths.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->addRow(Row::fromValues(['xlsx--11', 'xlsx--12']));
        $options->setColumnWidth(50.0, 1);
        $sheet = $writer->getCurrentSheet();
        $sheet->setColumnWidth(100.0, 1);
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('<cols', $xmlContents, 'No cols tag found in sheet');
        self::assertStringContainsString('<col min="1" max="1" width="100" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
    }

    public function testWritesMultipleColumnWidthsToSheetOverridingOptions(): void
    {
        $fileName = 'test_multiple_column_widths.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $options->setColumnWidth(50.0, 1, 2, 3);
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->addRow(Row::fromValues(['xlsx--11', 'xlsx--12', 'xlsx--13']));
        $sheet = $writer->getCurrentSheet();
        $sheet->setColumnWidth(100.0, 1, 2, 3);
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('<cols', $xmlContents, 'No cols tag found in sheet');
        self::assertStringContainsString('<col min="1" max="3" width="100" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
    }

    public function testCanWriteAFormula(): void
    {
        $fileName = 'test_formula.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->addRow(Row::fromValues([1]));
        $writer->addRow(Row::fromValues([2]));
        $writer->addRow(Row::fromValues(['=SUM(A1:A2)']));
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('<f>SUM(A1:A2)</f>', $xmlContents, 'Formula not found');
    }

    public function testCanSetSheetViewProperties(): void
    {
        $fileName = 'test_sheetview_properties.xlsx';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $writer->getCurrentSheet()->setSheetView(
            (new SheetView())
                ->setShowFormulas(true)
                ->setShowGridLines(false)
                ->setShowRowColHeaders(false)
                ->setShowZeroes(false)
                ->setRightToLeft(false)
                ->setTabSelected(false)
                ->setShowOutlineSymbols(false)
                ->setDefaultGridColor(false)
                ->setView('normal')
                ->setTopLeftCell('A2')
                ->setColorId(1)
                ->setZoomScale(50)
                ->setZoomScaleNormal(70)
                ->setZoomScalePageLayoutView(80)
                ->setWorkbookViewId(90)
                ->setFreezeColumn('B')
                ->setFreezeRow(2)
        );

        $writer->addRow(Row::fromValues([1]));
        $writer->addRow(Row::fromValues([2]));
        $writer->addRow(Row::fromValues(['=SUM(A1:A2)']));
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('<sheetView', $xmlContents);
        self::assertStringContainsString('<pane', $xmlContents);
    }

    private function writeDataToSheetWithCustomName(string $fileName, string $sheetName): Sheet
    {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $sheet = $writer->getCurrentSheet();
        $sheet->setName($sheetName);

        $writer->addRow(Row::fromValues(['xlsx--11', 'xlsx--12']));
        $writer->close();

        return $sheet;
    }

    /**
     * @return Sheet[]
     */
    private function writeDataToMultipleSheetsAndReturnSheets(string $fileName): array
    {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $writer->addRow(Row::fromValues(['xlsx--sheet1--11', 'xlsx--sheet1--12']));
        $writer->addNewSheetAndMakeItCurrent();
        $writer->addRow(Row::fromValues(['xlsx--sheet2--11', 'xlsx--sheet2--12', 'xlsx--sheet2--13']));

        $writer->close();

        return $writer->getSheets();
    }

    private function writeDataToHiddenSheet(string $fileName): void
    {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $sheet = $writer->getCurrentSheet();
        $sheet->setIsVisible(false);

        $writer->addRow(Row::fromValues(['xlsx--11', 'xlsx--12']));
        $writer->close();
    }

    private function assertSheetNameEquals(string $expectedName, string $fileName, string $message = ''): void
    {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $pathToWorkbookFile = $resourcePath.'#xl/workbook.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString("<sheet name=\"{$expectedName}\"", $xmlContents, $message);
    }
}
