<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

use DOMElement;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Reader\Wrapper\XMLReader;
use OpenSpout\TestUsingResource;
use OpenSpout\Writer\Common\Manager\Style\StyleMerger;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use OpenSpout\Writer\RowCreationHelper;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WriterWithStyleTest extends TestCase
{
    use RowCreationHelper;

    private Style $defaultStyle;

    protected function setUp(): void
    {
        $this->defaultStyle = (new Style());
    }

    public function testAddRowShouldThrowExceptionIfCallAddRowBeforeOpeningWriter(): void
    {
        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);

        $this->expectException(WriterNotOpenedException::class);
        $writer->addRow(Row::fromValues(['xlsx--11', 'xlsx--12'], $this->defaultStyle));
    }

    public function testAddRowShouldThrowExceptionIfCalledBeforeOpeningWriter(): void
    {
        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);

        $this->expectException(WriterNotOpenedException::class);
        $writer->addRow(Row::fromValues(['xlsx--11', 'xlsx--12'], $this->defaultStyle));
    }

    public function testAddRowShouldListAllUsedFontsInCreatedStylesXmlFile(): void
    {
        $fileName = 'test_add_row_should_list_all_used_fonts.xlsx';

        $style = (new Style())
            ->setFontBold()
            ->setFontItalic()
            ->setFontUnderline()
            ->setFontStrikethrough()
        ;
        $style2 = (new Style())
            ->setFontSize(15)
            ->setFontColor(Color::RED)
            ->setFontName('Cambria')
        ;

        $dataRows = [
            Row::fromValues(['xlsx--11', 'xlsx--12'], $style),
            Row::fromValues(['xlsx--21', 'xlsx--22'], $style2),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $fontsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'fonts');
        self::assertSame('3', $fontsDomElement->getAttribute('count'), 'There should be 3 fonts, including the default one.');

        $fontElements = $fontsDomElement->getElementsByTagName('font');
        self::assertSame(3, $fontElements->length, 'There should be 3 associated "font" elements, including the default one.');

        // First font should be the default one
        /** @var DOMElement $defaultFontElement */
        $defaultFontElement = $fontElements->item(0);
        $this->assertChildrenNumEquals(3, $defaultFontElement, 'The default font should only have 3 properties.');
        $this->assertFirstChildHasAttributeEquals((string) Options::DEFAULT_FONT_SIZE, $defaultFontElement, 'sz', 'val');
        $this->assertFirstChildHasAttributeEquals(Color::toARGB(Style::DEFAULT_FONT_COLOR), $defaultFontElement, 'color', 'rgb');
        $this->assertFirstChildHasAttributeEquals(Options::DEFAULT_FONT_NAME, $defaultFontElement, 'name', 'val');

        // Second font should contain data from the first created style
        /** @var DOMElement $secondFontElement */
        $secondFontElement = $fontElements->item(1);
        $this->assertChildrenNumEquals(7, $secondFontElement, 'The font should only have 7 properties (4 custom styles + 3 default styles).');
        $this->assertChildExists($secondFontElement, 'b');
        $this->assertChildExists($secondFontElement, 'i');
        $this->assertChildExists($secondFontElement, 'u');
        $this->assertChildExists($secondFontElement, 'strike');
        $this->assertFirstChildHasAttributeEquals((string) Options::DEFAULT_FONT_SIZE, $secondFontElement, 'sz', 'val');
        $this->assertFirstChildHasAttributeEquals(Color::toARGB(Style::DEFAULT_FONT_COLOR), $secondFontElement, 'color', 'rgb');
        $this->assertFirstChildHasAttributeEquals(Options::DEFAULT_FONT_NAME, $secondFontElement, 'name', 'val');

        // Third font should contain data from the second created style
        /** @var DOMElement $thirdFontElement */
        $thirdFontElement = $fontElements->item(2);
        $this->assertChildrenNumEquals(3, $thirdFontElement, 'The font should only have 3 properties.');
        $this->assertFirstChildHasAttributeEquals('15', $thirdFontElement, 'sz', 'val');
        $this->assertFirstChildHasAttributeEquals(Color::toARGB(Color::RED), $thirdFontElement, 'color', 'rgb');
        $this->assertFirstChildHasAttributeEquals('Cambria', $thirdFontElement, 'name', 'val');
    }

    public function testAddRowShouldApplyStyleToCells(): void
    {
        $fileName = 'test_add_row_should_apply_style_to_cells.xlsx';

        $style = (new Style())->setFontBold();
        $style2 = (new Style())->setFontSize(15);

        $dataRows = [
            Row::fromValues(['xlsx--11'], $style),
            Row::fromValues(['xlsx--21'], $style2),
            Row::fromValues(['xlsx--31']),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellDomElements = $this->getCellElementsFromSheetXmlFile($fileName);
        self::assertCount(3, $cellDomElements, 'There should be 3 cells.');

        self::assertSame('1', $cellDomElements[0]->getAttribute('s'));
        self::assertSame('2', $cellDomElements[1]->getAttribute('s'));
        self::assertSame('0', $cellDomElements[2]->getAttribute('s'));
    }

    public function testAddRowShouldApplyStyleToEmptyCellsIfNeeded(): void
    {
        $fileName = 'test_add_row_should_apply_style_to_empty_cells_if_needed.xlsx';

        $styleWithFont = (new Style())->setFontBold();
        $styleWithBackground = (new Style())->setBackgroundColor(Color::BLUE);

        $border = new Border(new BorderPart(Border::BOTTOM, Color::GREEN));
        $styleWithBorder = (new Style())->setBorder($border);

        $dataRows = [
            Row::fromValues(['xlsx--11', '', 'xlsx--13']),
            Row::fromValues(['xlsx--21', '', 'xlsx--23'], $styleWithFont),
            Row::fromValues(['xlsx--31', '', 'xlsx--33'], $styleWithBackground),
            Row::fromValues(['xlsx--41', '', 'xlsx--43'], $styleWithBorder),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellDomElements = $this->getCellElementsFromSheetXmlFile($fileName);

        // The first and second rows should not have a reference to the empty cell
        // The other rows should have the reference because style should be applied to them
        // So that's: 2 + 2 + 3 + 3 = 10 cells
        self::assertCount(10, $cellDomElements);

        // First row has 2 styled cells
        self::assertSame('0', $cellDomElements[0]->getAttribute('s'));
        self::assertSame('0', $cellDomElements[1]->getAttribute('s'));

        // Second row has 2 styled cells
        self::assertSame('1', $cellDomElements[2]->getAttribute('s'));
        self::assertSame('1', $cellDomElements[3]->getAttribute('s'));

        // Third row has 3 styled cells
        self::assertSame('2', $cellDomElements[4]->getAttribute('s'));
        self::assertSame('2', $cellDomElements[5]->getAttribute('s'));
        self::assertSame('2', $cellDomElements[6]->getAttribute('s'));

        // Third row has 3 styled cells
        self::assertSame('3', $cellDomElements[7]->getAttribute('s'));
        self::assertSame('3', $cellDomElements[8]->getAttribute('s'));
        self::assertSame('3', $cellDomElements[9]->getAttribute('s'));
    }

    public function testAddRowShouldReuseDuplicateStyles(): void
    {
        $fileName = 'test_add_row_should_reuse_duplicate_styles.xlsx';

        $style = (new Style())->setFontBold();
        $dataRows = $this->createStyledRowsFromValues([
            ['xlsx--11'],
            ['xlsx--21'],
        ], $style);

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellDomElements = $this->getCellElementsFromSheetXmlFile($fileName);
        self::assertSame('1', $cellDomElements[0]->getAttribute('s'));
        self::assertSame('1', $cellDomElements[1]->getAttribute('s'));
    }

    public function testAddRowWithNumFmtStyles(): void
    {
        $fileName = 'test_add_row_with_numfmt.xlsx';
        $style = (new Style())
            ->setFontBold()
            ->setFormat('0.00')// Builtin format
        ;
        $style2 = (new Style())
            ->setFontBold()
            ->setFormat('0.000')
        ;

        $dataRows = [
            Row::fromValues([1.123456789], $style),
            Row::fromValues([12.1], $style2),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $formatsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'numFmts');
        self::assertSame(
            '1',
            $formatsDomElement->getAttribute('count'),
            'There should be 2 formats, including the default one'
        );

        $cellXfsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');

        foreach (['2', '164'] as $index => $expected) {
            $xfElement = $cellXfsDomElement->getElementsByTagName('xf')->item($index + 1);
            self::assertSame($expected, $xfElement->getAttribute('numFmtId'));
        }
    }

    public function testAddRowShouldAddWrapTextAlignmentInfoInStylesXmlFileIfSpecified(): void
    {
        $fileName = 'test_add_row_should_add_wrap_text_alignment.xlsx';

        $style = (new Style())->setShouldWrapText();
        $dataRows = $this->createStyledRowsFromValues([
            ['xlsx--11', 'xlsx--12'],
        ], $style);

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellXfsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        $xfElement = $cellXfsDomElement->getElementsByTagName('xf')->item(1);
        self::assertSame('1', $xfElement->getAttribute('applyAlignment'));
        $this->assertFirstChildHasAttributeEquals('1', $xfElement, 'alignment', 'wrapText');
    }

    public function testAddRowShouldAddNegatedWrapTextAlignmentInfoInStylesXmlFileIfSpecified(): void
    {
        $fileName = 'test_add_row_should_add_negated_wrap_text_alignment.xlsx';

        $style = (new Style())->setShouldWrapText(false);
        $dataRows = $this->createStyledRowsFromValues([
            ['xlsx--11', 'xlsx--12'],
        ], $style);

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellXfsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        $xfElement = $cellXfsDomElement->getElementsByTagName('xf')->item(1);
        self::assertEquals(1, $xfElement->getAttribute('applyAlignment'));
        $this->assertFirstChildHasAttributeEquals('0', $xfElement, 'alignment', 'wrapText');
    }

    public function testAddRowShouldApplyWrapTextIfCellContainsNewLine(): void
    {
        $fileName = 'test_add_row_should_apply_wrap_text_if_new_lines.xlsx';

        $dataRows = $this->createStyledRowsFromValues([
            ["xlsx--11\nxlsx--11"],
            ['xlsx--21'],
        ], $this->defaultStyle);

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellXfsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        $xfElement = $cellXfsDomElement->getElementsByTagName('xf')->item(1);
        self::assertSame('1', $xfElement->getAttribute('applyAlignment'));
        $this->assertFirstChildHasAttributeEquals('1', $xfElement, 'alignment', 'wrapText');
    }

    public function testAddRowShouldApplyCellAlignment(): void
    {
        $fileName = 'test_add_row_should_apply_cell_alignment.xlsx';

        $rightAlignedStyle = (new Style())->setCellAlignment(CellAlignment::RIGHT);
        $dataRows = $this->createStyledRowsFromValues([['xlsx--11']], $rightAlignedStyle);

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellXfsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        $xfElement = $cellXfsDomElement->getElementsByTagName('xf')->item(1);
        self::assertSame('1', $xfElement->getAttribute('applyAlignment'));
        $this->assertFirstChildHasAttributeEquals(CellAlignment::RIGHT, $xfElement, 'alignment', 'horizontal');
    }

    public function testAddRowShouldApplyCellVerticalAlignment(): void
    {
        $fileName = 'test_add_row_should_apply_cell_alignment.xlsx';

        $rightAlignedStyle = (new Style())->setCellVerticalAlignment(CellVerticalAlignment::JUSTIFY);
        $dataRows = $this->createStyledRowsFromValues([['xlsx--11']], $rightAlignedStyle);

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellXfsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        $xfElement = $cellXfsDomElement->getElementsByTagName('xf')->item(1);
        self::assertSame('1', $xfElement->getAttribute('applyAlignment'));
        $this->assertFirstChildHasAttributeEquals(CellAlignment::JUSTIFY, $xfElement, 'alignment', 'vertical');
    }

    public function testAddRowShouldApplyShrinkToFit(): void
    {
        $fileName = 'test_add_row_should_apply_shrink_to_fit.xlsx';

        $shrinkToFitStyle = (new Style())->setShouldShrinkToFit();
        $dataRows = $this->createStyledRowsFromValues([['xlsx--11']], $shrinkToFitStyle);

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellXfsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        $xfElement = $cellXfsDomElement->getElementsByTagName('xf')->item(1);
        self::assertEquals(1, $xfElement->getAttribute('applyAlignment'));
        $this->assertFirstChildHasAttributeEquals('true', $xfElement, 'alignment', 'shrinkToFit');
    }

    public function testAddRowShouldSupportCellStyling(): void
    {
        $fileName = 'test_add_row_should_support_cell_styling.xlsx';

        $boldStyle = (new Style())->setFontBold();
        $underlineStyle = (new Style())->setFontUnderline();

        $dataRow = new Row([
            Cell::fromValue('xlsx--11', $boldStyle),
            Cell::fromValue('xlsx--12', $underlineStyle),
            Cell::fromValue('xlsx--13', $underlineStyle),
        ]);

        $this->writeToXLSXFile([$dataRow], $fileName);

        $cellDomElements = $this->getCellElementsFromSheetXmlFile($fileName);

        // First row should have 3 styled cells, with cell 2 and 3 sharing the same style
        self::assertSame('1', $cellDomElements[0]->getAttribute('s'));
        self::assertSame('2', $cellDomElements[1]->getAttribute('s'));
        self::assertSame('2', $cellDomElements[2]->getAttribute('s'));
    }

    public function testAddBackgroundColor(): void
    {
        $fileName = 'test_add_background_color.xlsx';

        $style = (new Style())->setBackgroundColor(Color::WHITE);
        $dataRows = $this->createStyledRowsFromValues([
            ['BgColor'],
        ], $style);

        $this->writeToXLSXFile($dataRows, $fileName);

        $fillsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'fills');
        self::assertSame('3', $fillsDomElement->getAttribute('count'), 'There should be 3 fills, including the 2 default ones');

        $fillsElements = $fillsDomElement->getElementsByTagName('fill');

        $thirdFillElement = $fillsElements->item(2); // Zero based
        $fgColor = $thirdFillElement->getElementsByTagName('fgColor')->item(0)->getAttribute('rgb');

        self::assertSame(Color::WHITE, $fgColor, 'The foreground color should equal white');

        $styleXfsElements = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        self::assertSame('2', $styleXfsElements->getAttribute('count'), '2 cell xfs present - a default one and a custom one');

        $lastChild = $styleXfsElements->lastChild;
        self::assertInstanceOf(DOMElement::class, $lastChild);
        $customFillId = $lastChild->getAttribute('fillId');
        self::assertSame(2, (int) $customFillId, 'The custom fill id should have the index 2');
    }

    public function testReuseBackgroundColorSharedDefinition(): void
    {
        $fileName = 'test_add_background_color_shared_definition.xlsx';

        $style = (new Style())->setBackgroundColor(Color::RED)->setFontBold();
        $style2 = (new Style())->setBackgroundColor(Color::RED);

        $dataRows = [
            Row::fromValues(['row-bold-background-red'], $style),
            Row::fromValues(['row-background-red'], $style2),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $fillsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'fills');
        self::assertSame(
            '3',
            $fillsDomElement->getAttribute('count'),
            'There should be 3 fills, including the 2 default ones'
        );

        $styleXfsElements = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        self::assertSame(
            '3',
            $styleXfsElements->getAttribute('count'),
            '3 cell xfs present - a default one and two custom ones'
        );

        /** @var DOMElement $styleXfsElementChild1 */
        $styleXfsElementChild1 = $styleXfsElements->childNodes->item(1);
        $firstCustomId = $styleXfsElementChild1->getAttribute('fillId');
        self::assertSame(2, (int) $firstCustomId, 'The first custom fill id should have the index 2');

        /** @var DOMElement $styleXfsElementChild2 */
        $styleXfsElementChild2 = $styleXfsElements->childNodes->item(2);
        $secondCustomId = $styleXfsElementChild2->getAttribute('fillId');
        self::assertSame(2, (int) $secondCustomId, 'The second custom fill id should have the index 2');
    }

    public function testBorders(): void
    {
        $fileName = 'test_borders.xlsx';

        $borderBottomGreenThickSolid = new Border(new BorderPart(Border::BOTTOM, Color::GREEN, Border::WIDTH_THICK, Border::STYLE_SOLID));
        $borderTopRedThinDashed = new Border(new BorderPart(Border::TOP, Color::RED, Border::WIDTH_THIN, Border::STYLE_DASHED));

        $styles = [
            (new Style())->setBorder($borderBottomGreenThickSolid),
            new Style(),
            (new Style())->setBorder($borderTopRedThinDashed),
        ];

        $dataRows = [
            Row::fromValues(['row-with-border-bottom-green-thick-solid'], $styles[0]),
            Row::fromValues(['row-without-border'], $styles[1]),
            Row::fromValues(['row-with-border-top-red-thin-dashed'], $styles[2]),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $borderElements = $this->getXmlSectionFromStylesXmlFile($fileName, 'borders');
        self::assertSame('3', $borderElements->getAttribute('count'), '3 borders present');

        $styleXfsElements = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        self::assertSame('3', $styleXfsElements->getAttribute('count'), '3 cell xfs present');
    }

    public function testBordersCorrectOrder(): void
    {
        // Border should be Left, Right, Top, Bottom
        $fileName = 'test_borders_correct_order.xlsx';

        $borders = new Border(
            new BorderPart(Border::RIGHT),
            new BorderPart(Border::TOP),
            new BorderPart(Border::LEFT),
            new BorderPart(Border::BOTTOM)
        );

        $style = (new Style())->setBorder($borders);

        $dataRows = $this->createStyledRowsFromValues([
            ['I am a teapot'],
        ], $style);

        $this->writeToXLSXFile($dataRows, $fileName);
        $borderElements = $this->getXmlSectionFromStylesXmlFile($fileName, 'borders');

        $correctOrdering = [
            'left', 'right', 'top', 'bottom',
        ];

        /** @var DOMElement $borderNode */
        foreach ($borderElements->childNodes as $borderNode) {
            $borderParts = $borderNode->childNodes;
            $ordering = [];

            foreach ($borderParts as $part) {
                if ($part instanceof DOMElement) {
                    $ordering[] = $part->nodeName;
                }
            }

            self::assertSame($correctOrdering, $ordering, 'The border parts are in correct ordering');
        }
    }

    public function testSetDefaultRowStyle(): void
    {
        $fileName = 'test_set_default_row_style.xlsx';
        $dataRows = $this->createRowsFromValues([['xlsx--11']]);

        $defaultFontSize = 50;
        $defaultStyle = (new Style())->setFontSize($defaultFontSize);

        $this->writeToXLSXFileWithDefaultStyle($dataRows, $fileName, $defaultStyle);

        $fontsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'fonts');
        $fontElements = $fontsDomElement->getElementsByTagName('font');
        self::assertSame(1, $fontElements->length, 'There should only be the default font.');

        $defaultFontElement = $fontElements->item(0);
        $this->assertFirstChildHasAttributeEquals((string) $defaultFontSize, $defaultFontElement, 'sz', 'val');
    }

    public function testReuseBorders(): void
    {
        $fileName = 'test_reuse_borders.xlsx';

        $borderLeft = new Border(new BorderPart(Border::LEFT));
        $borderLeftStyle = (new Style())->setBorder($borderLeft);

        $borderRight = new Border(new BorderPart(Border::RIGHT, Color::RED, Border::WIDTH_THICK));
        $borderRightStyle = (new Style())->setBorder($borderRight);

        $fontStyle = (new Style())->setFontBold();
        $emptyStyle = (new Style());

        $borderRightFontBoldStyle = (new StyleMerger())->merge($borderRightStyle, $fontStyle);

        $dataRows = [
            Row::fromValues(['Border-Left'], $borderLeftStyle),
            Row::fromValues(['Empty'], $emptyStyle),
            Row::fromValues(['Font-Bold'], $fontStyle),
            Row::fromValues(['Border-Right'], $borderRightStyle),
            Row::fromValues(['Border-Right-Font-Bold'], $borderRightFontBoldStyle),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $borderElements = $this->getXmlSectionFromStylesXmlFile($fileName, 'borders');

        self::assertSame('3', $borderElements->getAttribute('count'), '3 borders in count attribute');
        self::assertSame(3, $borderElements->childNodes->length, '3 border childnodes present');

        /** @var DOMElement $firstBorder */
        $firstBorder = $borderElements->childNodes->item(1); // 0  = default border
        $leftStyle = $firstBorder->getElementsByTagName('left')->item(0)->getAttribute('style');
        self::assertSame('medium', $leftStyle, 'Style is medium');

        /** @var DOMElement $secondBorder */
        $secondBorder = $borderElements->childNodes->item(2);
        $rightStyle = $secondBorder->getElementsByTagName('right')->item(0)->getAttribute('style');
        self::assertSame('thick', $rightStyle, 'Style is thick');

        $styleXfsElements = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');

        // A rather relaxed test
        // Where a border is applied - the borderId attribute has to be greater than 0
        $bordersApplied = 0;

        /** @var DOMElement $node */
        foreach ($styleXfsElements->childNodes as $node) {
            $shouldApplyBorder = (1 === (int) $node->getAttribute('applyBorder'));
            if ($shouldApplyBorder) {
                ++$bordersApplied;
                self::assertGreaterThan(0, (int) $node->getAttribute('borderId'), 'BorderId is greater than 0');
            } else {
                self::assertSame(0, (int) $node->getAttribute('borderId'), 'BorderId is 0');
            }
        }

        self::assertSame(3, $bordersApplied, 'Three borders have been applied');
    }

    /**
     * @param Row[] $allRows
     */
    private function writeToXLSXFile(array $allRows, string $fileName): Writer
    {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $options->SHOULD_USE_INLINE_STRINGS = true;
        $writer = new Writer($options);

        $writer->openToFile($resourcePath);
        $writer->addRows($allRows);
        $writer->close();

        return $writer;
    }

    /**
     * @param Row[] $allRows
     */
    private function writeToXLSXFileWithDefaultStyle(array $allRows, string $fileName, ?Style $defaultStyle): Writer
    {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $options->SHOULD_USE_INLINE_STRINGS = true;
        $options->DEFAULT_ROW_STYLE = $defaultStyle;
        $writer = new Writer($options);

        $writer->openToFile($resourcePath);
        $writer->addRows($allRows);
        $writer->close();

        return $writer;
    }

    private function getXmlSectionFromStylesXmlFile(string $fileName, string $section): DOMElement
    {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $xmlReader = new XMLReader();
        $xmlReader->openFileInZip($resourcePath, 'xl/styles.xml');
        $xmlReader->readUntilNodeFound($section);

        /** @var DOMElement $xmlSection */
        $xmlSection = $xmlReader->expand();

        $xmlReader->close();

        return $xmlSection;
    }

    /**
     * @return DOMElement[]
     */
    private function getCellElementsFromSheetXmlFile(string $fileName): array
    {
        $cellElements = [];

        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $xmlReader = new XMLReader();
        $xmlReader->openFileInZip($resourcePath, 'xl/worksheets/sheet1.xml');

        while ($xmlReader->read()) {
            if ($xmlReader->isPositionedOnStartingNode('c')) {
                /** @var DOMElement $cellElement */
                $cellElement = $xmlReader->expand();
                $cellElements[] = $cellElement;
            }
        }

        $xmlReader->close();

        return $cellElements;
    }

    private function assertFirstChildHasAttributeEquals(string $expectedValue, DOMElement $parentElement, string $childTagName, string $attributeName): void
    {
        self::assertSame($expectedValue, $parentElement->getElementsByTagName($childTagName)->item(0)->getAttribute($attributeName));
    }

    private function assertChildrenNumEquals(int $expectedNumber, DOMElement $parentElement, string $message): void
    {
        self::assertSame($expectedNumber, $parentElement->getElementsByTagName('*')->length, $message);
    }

    private function assertChildExists(DOMElement $parentElement, string $childTagName): void
    {
        self::assertSame(1, $parentElement->getElementsByTagName($childTagName)->length);
    }
}
