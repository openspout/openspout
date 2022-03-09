<?php

namespace OpenSpout\Writer\XLSX;

use OpenSpout\TestUsingResource;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\Common\Entity\Sheet;
use OpenSpout\Writer\Exception\InvalidSheetNameException;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use OpenSpout\Writer\RowCreationHelper;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use PHPUnit\Framework\TestCase;

/**
 * Class SheetTest.
 *
 * @internal
 * @coversNothing
 */
final class SheetTest extends TestCase
{
    use RowCreationHelper;
    use TestUsingResource;

    public function testGetSheetIndex()
    {
        $sheets = $this->writeDataToMultipleSheetsAndReturnSheets('test_get_sheet_index.xlsx');

        static::assertCount(2, $sheets, '2 sheets should have been created');
        static::assertSame(0, $sheets[0]->getIndex(), 'The first sheet should be index 0');
        static::assertSame(1, $sheets[1]->getIndex(), 'The second sheet should be index 1');
    }

    public function testGetSheetName()
    {
        $sheets = $this->writeDataToMultipleSheetsAndReturnSheets('test_get_sheet_name.xlsx');

        static::assertCount(2, $sheets, '2 sheets should have been created');
        static::assertSame('Sheet1', $sheets[0]->getName(), 'Invalid name for the first sheet');
        static::assertSame('Sheet2', $sheets[1]->getName(), 'Invalid name for the second sheet');
    }

    public function testSetSheetNameShouldCreateSheetWithCustomName()
    {
        $fileName = 'test_set_name_should_create_sheet_with_custom_name.xlsx';
        $customSheetName = 'CustomName';
        $this->writeDataToSheetWithCustomName($fileName, $customSheetName);

        $this->assertSheetNameEquals($customSheetName, $fileName, "The sheet name should have been changed to '{$customSheetName}'");
    }

    public function testSetSheetNameShouldThrowWhenNameIsAlreadyUsed()
    {
        $this->expectException(InvalidSheetNameException::class);

        $fileName = 'test_set_name_with_non_unique_name.xlsx';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToFile($resourcePath);

        $customSheetName = 'Sheet name';

        $sheet = $writer->getCurrentSheet();
        $sheet->setName($customSheetName);

        $writer->addNewSheetAndMakeItCurrent();
        $sheet = $writer->getCurrentSheet();
        $sheet->setName($customSheetName);
    }

    public function testSetSheetVisibilityShouldCreateSheetHidden()
    {
        $fileName = 'test_set_visibility_should_create_sheet_hidden.xlsx';
        $this->writeDataToHiddenSheet($fileName);

        $resourcePath = $this->getGeneratedResourcePath($fileName);
        $pathToWorkbookFile = $resourcePath.'#xl/workbook.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        static::assertStringContainsString(' state="hidden"', $xmlContents, 'The sheet visibility should have been changed to "hidden"');
    }

    public function testThrowsIfWorkbookIsNotInitialized()
    {
        $this->expectException(WriterNotOpenedException::class);
        $writer = WriterEntityFactory::createXLSXWriter();

        $writer->addRow($this->createRowFromValues([]));
    }

    public function testWritesDefaultCellSizesIfSet()
    {
        $fileName = 'test_writes_default_cell_sizes_if_set.xlsx';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->setDefaultColumnWidth(10.0);
        $writer->setDefaultRowHeight(20.0);
        $writer->openToFile($resourcePath);
        $writer->addRow($this->createRowFromValues(['xlsx--11', 'xlsx--12']));
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        static::assertStringContainsString('<sheetFormatPr', $xmlContents, 'No sheetFormatPr tag found in sheet');
        static::assertStringContainsString(' defaultColWidth="10', $xmlContents, 'No default column width found in sheet');
        static::assertStringContainsString(' defaultRowHeight="20', $xmlContents, 'No default row height found in sheet');
        static::assertStringContainsString(' customHeight="1"', $xmlContents, 'No row height override flag found in row');
    }

    public function testWritesDefaultRequiredRowHeightIfOmitted()
    {
        $fileName = 'test_writes_default_required_row_height_if_omitted.xlsx';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->setDefaultColumnWidth(10.0);
        $writer->openToFile($resourcePath);

        $writer->addRow($this->createRowFromValues(['xlsx--11', 'xlsx--12']));
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        static::assertStringContainsString('<sheetFormatPr', $xmlContents, 'No sheetFormatPr tag found in sheet');
        static::assertStringContainsString(' defaultColWidth="10', $xmlContents, 'No default column width found in sheet');
        static::assertStringContainsString(' defaultRowHeight="0', $xmlContents, 'No default row height found in sheet');
    }

    public function testWritesColumnWidths()
    {
        $fileName = 'test_column_widths.xlsx';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToFile($resourcePath);
        $writer->setColumnWidth(100.0, 1);
        $writer->addRow($this->createRowFromValues(['xlsx--11', 'xlsx--12']));
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        static::assertStringContainsString('<cols', $xmlContents, 'No cols tag found in sheet');
        static::assertStringContainsString('<col min="1" max="1" width="100" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
    }

    public function testWritesMultipleColumnWidths()
    {
        $fileName = 'test_multiple_column_widths.xlsx';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToFile($resourcePath);
        $writer->setColumnWidth(100.0, 1, 2, 3);
        $writer->addRow($this->createRowFromValues(['xlsx--11', 'xlsx--12', 'xlsx--13']));
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        static::assertStringContainsString('<cols', $xmlContents, 'No cols tag found in sheet');
        static::assertStringContainsString('<col min="1" max="3" width="100" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
    }

    public function testWritesMultipleColumnWidthsInRanges()
    {
        $fileName = 'test_multiple_column_widths_in_ranges.xlsx';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToFile($resourcePath);
        $writer->setColumnWidth(50.0, 1, 3, 4, 6);
        $writer->setColumnWidth(100.0, 2, 5);
        $writer->addRow($this->createRowFromValues(['xlsx--11', 'xlsx--12', 'xlsx--13', 'xlsx--14', 'xlsx--15', 'xlsx--16']));
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        static::assertStringContainsString('<cols', $xmlContents, 'No cols tag found in sheet');
        static::assertStringContainsString('<col min="1" max="1" width="50" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
        static::assertStringContainsString('<col min="3" max="4" width="50" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
        static::assertStringContainsString('<col min="6" max="6" width="50" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
        static::assertStringContainsString('<col min="2" max="2" width="100" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
        static::assertStringContainsString('<col min="5" max="5" width="100" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
    }

    public function testCanTakeColumnWidthsAsRange()
    {
        $fileName = 'test_column_widths_as_ranges.xlsx';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToFile($resourcePath);
        $writer->setColumnWidthForRange(50.0, 1, 3);
        $writer->addRow($this->createRowFromValues(['xlsx--11', 'xlsx--12', 'xlsx--13']));
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        static::assertStringContainsString('<cols', $xmlContents, 'No cols tag found in sheet');
        static::assertStringContainsString('<col min="1" max="3" width="50" customWidth="true"', $xmlContents, 'No expected column width definition found in sheet');
    }

    public function testCanWriteAFormula()
    {
        $fileName = 'test_formula.xlsx';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToFile($resourcePath);
        $writer->addRow($this->createRowFromValues([1]));
        $writer->addRow($this->createRowFromValues([2]));
        $writer->addRow($this->createRowFromValues(['=SUM(A1:A2)']));
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        static::assertStringContainsString('<f>SUM(A1:A2)</f>', $xmlContents, 'Formula not found');
    }

    public function testCanSetSheetViewProperties()
    {
        $fileName = 'test_sheetview_properties.xlsx';
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToFile($resourcePath);

        $writer->getCurrentSheet()->setSheetView(
            (new SheetView())
                ->setShowFormulas(true)
                ->setShowGridLines(false)
                ->setShowRowColHeaders(false)
                ->setShowZeroes(false)
                ->setRightToLeft(false)
                ->setTabSelected(false)
                ->setColorId(1)
                ->setFreezeColumn('B')
                ->setFreezeRow(2)
        );

        $writer->addRow($this->createRowFromValues([1]));
        $writer->addRow($this->createRowFromValues([2]));
        $writer->addRow($this->createRowFromValues(['=SUM(A1:A2)']));
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#xl/worksheets/sheet1.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        static::assertStringContainsString('<sheetView', $xmlContents);
        static::assertStringContainsString('<pane', $xmlContents);
    }

    /**
     * @param string $fileName
     * @param string $sheetName
     *
     * @return Sheet
     */
    private function writeDataToSheetWithCustomName($fileName, $sheetName)
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToFile($resourcePath);

        $sheet = $writer->getCurrentSheet();
        $sheet->setName($sheetName);

        $writer->addRow($this->createRowFromValues(['xlsx--11', 'xlsx--12']));
        $writer->close();

        return $sheet;
    }

    /**
     * @param string $fileName
     *
     * @return Sheet[]
     */
    private function writeDataToMultipleSheetsAndReturnSheets($fileName)
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToFile($resourcePath);

        $writer->addRow($this->createRowFromValues(['xlsx--sheet1--11', 'xlsx--sheet1--12']));
        $writer->addNewSheetAndMakeItCurrent();
        $writer->addRow($this->createRowFromValues(['xlsx--sheet2--11', 'xlsx--sheet2--12', 'xlsx--sheet2--13']));

        $writer->close();

        return $writer->getSheets();
    }

    /**
     * @param string $fileName
     */
    private function writeDataToHiddenSheet($fileName)
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToFile($resourcePath);

        $sheet = $writer->getCurrentSheet();
        $sheet->setIsVisible(false);

        $writer->addRow($this->createRowFromValues(['xlsx--11', 'xlsx--12']));
        $writer->close();
    }

    /**
     * @param string $expectedName
     * @param string $fileName
     * @param string $message
     */
    private function assertSheetNameEquals($expectedName, $fileName, $message = '')
    {
        $resourcePath = $this->getGeneratedResourcePath($fileName);
        $pathToWorkbookFile = $resourcePath.'#xl/workbook.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        static::assertStringContainsString("<sheet name=\"{$expectedName}\"", $xmlContents, $message);
    }
}
