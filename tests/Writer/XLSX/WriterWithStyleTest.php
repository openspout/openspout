<?php

namespace OpenSpout\Writer\XLSX;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Reader\Wrapper\XMLReader;
use OpenSpout\TestUsingResource;
use OpenSpout\Writer\Common\Creator\Style\BorderBuilder;
use OpenSpout\Writer\Common\Creator\Style\StyleBuilder;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;
use OpenSpout\Writer\Common\Manager\Style\StyleMerger;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use OpenSpout\Writer\RowCreationHelper;
use OpenSpout\Writer\XLSX\Manager\OptionsManager;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WriterWithStyleTest extends TestCase
{
    use RowCreationHelper;
    use TestUsingResource;

    /** @var \OpenSpout\Common\Entity\Style\Style */
    private $defaultStyle;

    protected function setUp(): void
    {
        $this->defaultStyle = (new StyleBuilder())->build();
    }

    public function testAddRowShouldThrowExceptionIfCallAddRowBeforeOpeningWriter()
    {
        $this->expectException(WriterNotOpenedException::class);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->addRow($this->createStyledRowFromValues(['xlsx--11', 'xlsx--12'], $this->defaultStyle));
    }

    public function testAddRowShouldThrowExceptionIfCalledBeforeOpeningWriter()
    {
        $this->expectException(WriterNotOpenedException::class);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->addRow($this->createStyledRowFromValues(['xlsx--11', 'xlsx--12'], $this->defaultStyle));
    }

    public function testAddRowShouldListAllUsedFontsInCreatedStylesXmlFile()
    {
        $fileName = 'test_add_row_should_list_all_used_fonts.xlsx';

        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFontItalic()
            ->setFontUnderline()
            ->setFontStrikethrough()
            ->build()
        ;
        $style2 = (new StyleBuilder())
            ->setFontSize(15)
            ->setFontColor(Color::RED)
            ->setFontName('Cambria')
            ->build()
        ;

        $dataRows = [
            $this->createStyledRowFromValues(['xlsx--11', 'xlsx--12'], $style),
            $this->createStyledRowFromValues(['xlsx--21', 'xlsx--22'], $style2),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $fontsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'fonts');
        static::assertSame('3', $fontsDomElement->getAttribute('count'), 'There should be 3 fonts, including the default one.');

        $fontElements = $fontsDomElement->getElementsByTagName('font');
        static::assertSame(3, $fontElements->length, 'There should be 3 associated "font" elements, including the default one.');

        // First font should be the default one
        /** @var \DOMElement $defaultFontElement */
        $defaultFontElement = $fontElements->item(0);
        $this->assertChildrenNumEquals(3, $defaultFontElement, 'The default font should only have 3 properties.');
        $this->assertFirstChildHasAttributeEquals((string) OptionsManager::DEFAULT_FONT_SIZE, $defaultFontElement, 'sz', 'val');
        $this->assertFirstChildHasAttributeEquals(Color::toARGB(Style::DEFAULT_FONT_COLOR), $defaultFontElement, 'color', 'rgb');
        $this->assertFirstChildHasAttributeEquals(OptionsManager::DEFAULT_FONT_NAME, $defaultFontElement, 'name', 'val');

        // Second font should contain data from the first created style
        /** @var \DOMElement $secondFontElement */
        $secondFontElement = $fontElements->item(1);
        $this->assertChildrenNumEquals(7, $secondFontElement, 'The font should only have 7 properties (4 custom styles + 3 default styles).');
        $this->assertChildExists($secondFontElement, 'b');
        $this->assertChildExists($secondFontElement, 'i');
        $this->assertChildExists($secondFontElement, 'u');
        $this->assertChildExists($secondFontElement, 'strike');
        $this->assertFirstChildHasAttributeEquals((string) OptionsManager::DEFAULT_FONT_SIZE, $secondFontElement, 'sz', 'val');
        $this->assertFirstChildHasAttributeEquals(Color::toARGB(Style::DEFAULT_FONT_COLOR), $secondFontElement, 'color', 'rgb');
        $this->assertFirstChildHasAttributeEquals(OptionsManager::DEFAULT_FONT_NAME, $secondFontElement, 'name', 'val');

        // Third font should contain data from the second created style
        /** @var \DOMElement $thirdFontElement */
        $thirdFontElement = $fontElements->item(2);
        $this->assertChildrenNumEquals(3, $thirdFontElement, 'The font should only have 3 properties.');
        $this->assertFirstChildHasAttributeEquals('15', $thirdFontElement, 'sz', 'val');
        $this->assertFirstChildHasAttributeEquals(Color::toARGB(Color::RED), $thirdFontElement, 'color', 'rgb');
        $this->assertFirstChildHasAttributeEquals('Cambria', $thirdFontElement, 'name', 'val');
    }

    public function testAddRowShouldApplyStyleToCells()
    {
        $fileName = 'test_add_row_should_apply_style_to_cells.xlsx';

        $style = (new StyleBuilder())->setFontBold()->build();
        $style2 = (new StyleBuilder())->setFontSize(15)->build();

        $dataRows = [
            $this->createStyledRowFromValues(['xlsx--11'], $style),
            $this->createStyledRowFromValues(['xlsx--21'], $style2),
            $this->createRowFromValues(['xlsx--31']),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellDomElements = $this->getCellElementsFromSheetXmlFile($fileName);
        static::assertCount(3, $cellDomElements, 'There should be 3 cells.');

        static::assertSame('1', $cellDomElements[0]->getAttribute('s'));
        static::assertSame('2', $cellDomElements[1]->getAttribute('s'));
        static::assertSame('0', $cellDomElements[2]->getAttribute('s'));
    }

    public function testAddRowShouldApplyStyleToEmptyCellsIfNeeded()
    {
        $fileName = 'test_add_row_should_apply_style_to_empty_cells_if_needed.xlsx';

        $styleWithFont = (new StyleBuilder())->setFontBold()->build();
        $styleWithBackground = (new StyleBuilder())->setBackgroundColor(Color::BLUE)->build();

        $border = (new BorderBuilder())->setBorderBottom(Color::GREEN)->build();
        $styleWithBorder = (new StyleBuilder())->setBorder($border)->build();

        $dataRows = [
            $this->createRowFromValues(['xlsx--11', '', 'xlsx--13']),
            $this->createStyledRowFromValues(['xlsx--21', '', 'xlsx--23'], $styleWithFont),
            $this->createStyledRowFromValues(['xlsx--31', '', 'xlsx--33'], $styleWithBackground),
            $this->createStyledRowFromValues(['xlsx--41', '', 'xlsx--43'], $styleWithBorder),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellDomElements = $this->getCellElementsFromSheetXmlFile($fileName);

        // The first and second rows should not have a reference to the empty cell
        // The other rows should have the reference because style should be applied to them
        // So that's: 2 + 2 + 3 + 3 = 10 cells
        static::assertCount(10, $cellDomElements);

        // First row has 2 styled cells
        static::assertSame('0', $cellDomElements[0]->getAttribute('s'));
        static::assertSame('0', $cellDomElements[1]->getAttribute('s'));

        // Second row has 2 styled cells
        static::assertSame('1', $cellDomElements[2]->getAttribute('s'));
        static::assertSame('1', $cellDomElements[3]->getAttribute('s'));

        // Third row has 3 styled cells
        static::assertSame('2', $cellDomElements[4]->getAttribute('s'));
        static::assertSame('2', $cellDomElements[5]->getAttribute('s'));
        static::assertSame('2', $cellDomElements[6]->getAttribute('s'));

        // Third row has 3 styled cells
        static::assertSame('3', $cellDomElements[7]->getAttribute('s'));
        static::assertSame('3', $cellDomElements[8]->getAttribute('s'));
        static::assertSame('3', $cellDomElements[9]->getAttribute('s'));
    }

    public function testAddRowShouldReuseDuplicateStyles()
    {
        $fileName = 'test_add_row_should_reuse_duplicate_styles.xlsx';

        $style = (new StyleBuilder())->setFontBold()->build();
        $dataRows = $this->createStyledRowsFromValues([
            ['xlsx--11'],
            ['xlsx--21'],
        ], $style);

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellDomElements = $this->getCellElementsFromSheetXmlFile($fileName);
        static::assertSame('1', $cellDomElements[0]->getAttribute('s'));
        static::assertSame('1', $cellDomElements[1]->getAttribute('s'));
    }

    public function testAddRowWithNumFmtStyles()
    {
        $fileName = 'test_add_row_with_numfmt.xlsx';
        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFormat('0.00')//Builtin format
            ->build()
        ;
        $style2 = (new StyleBuilder())
            ->setFontBold()
            ->setFormat('0.000')
            ->build()
        ;

        $dataRows = [
            $this->createStyledRowFromValues([1.123456789], $style),
            $this->createStyledRowFromValues([12.1], $style2),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $formatsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'numFmts');
        static::assertSame(
            '1',
            $formatsDomElement->getAttribute('count'),
            'There should be 2 formats, including the default one'
        );

        $cellXfsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');

        foreach (['2', '164'] as $index => $expected) {
            $xfElement = $cellXfsDomElement->getElementsByTagName('xf')->item($index + 1);
            static::assertSame($expected, $xfElement->getAttribute('numFmtId'));
        }
    }

    public function testAddRowShouldAddWrapTextAlignmentInfoInStylesXmlFileIfSpecified()
    {
        $fileName = 'test_add_row_should_add_wrap_text_alignment.xlsx';

        $style = (new StyleBuilder())->setShouldWrapText()->build();
        $dataRows = $this->createStyledRowsFromValues([
            ['xlsx--11', 'xlsx--12'],
        ], $style);

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellXfsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        $xfElement = $cellXfsDomElement->getElementsByTagName('xf')->item(1);
        static::assertSame('1', $xfElement->getAttribute('applyAlignment'));
        $this->assertFirstChildHasAttributeEquals('1', $xfElement, 'alignment', 'wrapText');
    }

    public function testAddRowShouldApplyWrapTextIfCellContainsNewLine()
    {
        $fileName = 'test_add_row_should_apply_wrap_text_if_new_lines.xlsx';

        $dataRows = $this->createStyledRowsFromValues([
            ["xlsx--11\nxlsx--11"],
            ['xlsx--21'],
        ], $this->defaultStyle);

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellXfsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        $xfElement = $cellXfsDomElement->getElementsByTagName('xf')->item(1);
        static::assertSame('1', $xfElement->getAttribute('applyAlignment'));
        $this->assertFirstChildHasAttributeEquals('1', $xfElement, 'alignment', 'wrapText');
    }

    public function testAddRowShouldApplyCellAlignment()
    {
        $fileName = 'test_add_row_should_apply_cell_alignment.xlsx';

        $rightAlignedStyle = (new StyleBuilder())->setCellAlignment(CellAlignment::RIGHT)->build();
        $dataRows = $this->createStyledRowsFromValues([['xlsx--11']], $rightAlignedStyle);

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellXfsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        $xfElement = $cellXfsDomElement->getElementsByTagName('xf')->item(1);
        static::assertSame('1', $xfElement->getAttribute('applyAlignment'));
        $this->assertFirstChildHasAttributeEquals(CellAlignment::RIGHT, $xfElement, 'alignment', 'horizontal');
    }

    public function testAddRowShouldApplyShrinkToFit()
    {
        $fileName = 'test_add_row_should_apply_shrink_to_fit.xlsx';

        $shrinkToFitStyle = (new StyleBuilder())->setShouldShrinkToFit()->build();
        $dataRows = $this->createStyledRowsFromValues([['xlsx--11']], $shrinkToFitStyle);

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellXfsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        $xfElement = $cellXfsDomElement->getElementsByTagName('xf')->item(1);
        static::assertEquals(1, $xfElement->getAttribute('applyAlignment'));
        $this->assertFirstChildHasAttributeEquals('true', $xfElement, 'alignment', 'shrinkToFit');
    }

    public function testAddRowShouldSupportCellStyling()
    {
        $fileName = 'test_add_row_should_support_cell_styling.xlsx';

        $boldStyle = (new StyleBuilder())->setFontBold()->build();
        $underlineStyle = (new StyleBuilder())->setFontUnderline()->build();

        $dataRow = WriterEntityFactory::createRow([
            WriterEntityFactory::createCell('xlsx--11', $boldStyle),
            WriterEntityFactory::createCell('xlsx--12', $underlineStyle),
            WriterEntityFactory::createCell('xlsx--13', $underlineStyle),
        ]);

        $this->writeToXLSXFile([$dataRow], $fileName);

        $cellDomElements = $this->getCellElementsFromSheetXmlFile($fileName);

        // First row should have 3 styled cells, with cell 2 and 3 sharing the same style
        static::assertSame('1', $cellDomElements[0]->getAttribute('s'));
        static::assertSame('2', $cellDomElements[1]->getAttribute('s'));
        static::assertSame('2', $cellDomElements[2]->getAttribute('s'));
    }

    public function testAddBackgroundColor()
    {
        $fileName = 'test_add_background_color.xlsx';

        $style = (new StyleBuilder())->setBackgroundColor(Color::WHITE)->build();
        $dataRows = $this->createStyledRowsFromValues([
            ['BgColor'],
        ], $style);

        $this->writeToXLSXFile($dataRows, $fileName);

        $fillsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'fills');
        static::assertSame('3', $fillsDomElement->getAttribute('count'), 'There should be 3 fills, including the 2 default ones');

        $fillsElements = $fillsDomElement->getElementsByTagName('fill');

        $thirdFillElement = $fillsElements->item(2); // Zero based
        $fgColor = $thirdFillElement->getElementsByTagName('fgColor')->item(0)->getAttribute('rgb');

        static::assertSame(Color::WHITE, $fgColor, 'The foreground color should equal white');

        $styleXfsElements = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        static::assertSame('2', $styleXfsElements->getAttribute('count'), '2 cell xfs present - a default one and a custom one');

        /** @var \DOMElement $lastChild */
        $lastChild = $styleXfsElements->lastChild;
        $customFillId = $lastChild->getAttribute('fillId');
        static::assertSame(2, (int) $customFillId, 'The custom fill id should have the index 2');
    }

    public function testReuseBackgroundColorSharedDefinition()
    {
        $fileName = 'test_add_background_color_shared_definition.xlsx';

        $style = (new StyleBuilder())->setBackgroundColor(Color::RED)->setFontBold()->build();
        $style2 = (new StyleBuilder())->setBackgroundColor(Color::RED)->build();

        $dataRows = [
            $this->createStyledRowFromValues(['row-bold-background-red'], $style),
            $this->createStyledRowFromValues(['row-background-red'], $style2),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $fillsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'fills');
        static::assertSame(
            '3',
            $fillsDomElement->getAttribute('count'),
            'There should be 3 fills, including the 2 default ones'
        );

        $styleXfsElements = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        static::assertSame(
            '3',
            $styleXfsElements->getAttribute('count'),
            '3 cell xfs present - a default one and two custom ones'
        );

        /** @var \DOMElement $styleXfsElementChild1 */
        $styleXfsElementChild1 = $styleXfsElements->childNodes->item(1);
        $firstCustomId = $styleXfsElementChild1->getAttribute('fillId');
        static::assertSame(2, (int) $firstCustomId, 'The first custom fill id should have the index 2');

        /** @var \DOMElement $styleXfsElementChild2 */
        $styleXfsElementChild2 = $styleXfsElements->childNodes->item(2);
        $secondCustomId = $styleXfsElementChild2->getAttribute('fillId');
        static::assertSame(2, (int) $secondCustomId, 'The second custom fill id should have the index 2');
    }

    public function testBorders()
    {
        $fileName = 'test_borders.xlsx';

        $borderBottomGreenThickSolid = (new BorderBuilder())
            ->setBorderBottom(Color::GREEN, Border::WIDTH_THICK, Border::STYLE_SOLID)->build();

        $borderTopRedThinDashed = (new BorderBuilder())
            ->setBorderTop(Color::RED, Border::WIDTH_THIN, Border::STYLE_DASHED)->build();

        $styles = [
            (new StyleBuilder())->setBorder($borderBottomGreenThickSolid)->build(),
            (new StyleBuilder())->build(),
            (new StyleBuilder())->setBorder($borderTopRedThinDashed)->build(),
        ];

        $dataRows = [
            $this->createStyledRowFromValues(['row-with-border-bottom-green-thick-solid'], $styles[0]),
            $this->createStyledRowFromValues(['row-without-border'], $styles[1]),
            $this->createStyledRowFromValues(['row-with-border-top-red-thin-dashed'], $styles[2]),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $borderElements = $this->getXmlSectionFromStylesXmlFile($fileName, 'borders');
        static::assertSame('3', $borderElements->getAttribute('count'), '3 borders present');

        $styleXfsElements = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        static::assertSame('3', $styleXfsElements->getAttribute('count'), '3 cell xfs present');
    }

    public function testBordersCorrectOrder()
    {
        // Border should be Left, Right, Top, Bottom
        $fileName = 'test_borders_correct_order.xlsx';

        $borders = (new BorderBuilder())
            ->setBorderRight()
            ->setBorderTop()
            ->setBorderLeft()
            ->setBorderBottom()
            ->build()
        ;

        $style = (new StyleBuilder())->setBorder($borders)->build();

        $dataRows = $this->createStyledRowsFromValues([
            ['I am a teapot'],
        ], $style);

        $this->writeToXLSXFile($dataRows, $fileName);
        $borderElements = $this->getXmlSectionFromStylesXmlFile($fileName, 'borders');

        $correctOrdering = [
            'left', 'right', 'top', 'bottom',
        ];

        /** @var \DOMElement $borderNode */
        foreach ($borderElements->childNodes as $borderNode) {
            $borderParts = $borderNode->childNodes;
            $ordering = [];

            foreach ($borderParts as $part) {
                if ($part instanceof \DOMElement) {
                    $ordering[] = $part->nodeName;
                }
            }

            static::assertSame($correctOrdering, $ordering, 'The border parts are in correct ordering');
        }
    }

    public function testSetDefaultRowStyle()
    {
        $fileName = 'test_set_default_row_style.xlsx';
        $dataRows = $this->createRowsFromValues([['xlsx--11']]);

        $defaultFontSize = 50;
        $defaultStyle = (new StyleBuilder())->setFontSize($defaultFontSize)->build();

        $this->writeToXLSXFileWithDefaultStyle($dataRows, $fileName, $defaultStyle);

        $fontsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'fonts');
        $fontElements = $fontsDomElement->getElementsByTagName('font');
        static::assertSame(1, $fontElements->length, 'There should only be the default font.');

        $defaultFontElement = $fontElements->item(0);
        $this->assertFirstChildHasAttributeEquals((string) $defaultFontSize, $defaultFontElement, 'sz', 'val');
    }

    public function testReuseBorders()
    {
        $fileName = 'test_reuse_borders.xlsx';

        $borderLeft = (new BorderBuilder())->setBorderLeft()->build();
        $borderLeftStyle = (new StyleBuilder())->setBorder($borderLeft)->build();

        $borderRight = (new BorderBuilder())->setBorderRight(Color::RED, Border::WIDTH_THICK)->build();
        $borderRightStyle = (new StyleBuilder())->setBorder($borderRight)->build();

        $fontStyle = (new StyleBuilder())->setFontBold()->build();
        $emptyStyle = (new StyleBuilder())->build();

        $borderRightFontBoldStyle = (new StyleMerger())->merge($borderRightStyle, $fontStyle);

        $dataRows = [
            $this->createStyledRowFromValues(['Border-Left'], $borderLeftStyle),
            $this->createStyledRowFromValues(['Empty'], $emptyStyle),
            $this->createStyledRowFromValues(['Font-Bold'], $fontStyle),
            $this->createStyledRowFromValues(['Border-Right'], $borderRightStyle),
            $this->createStyledRowFromValues(['Border-Right-Font-Bold'], $borderRightFontBoldStyle),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $borderElements = $this->getXmlSectionFromStylesXmlFile($fileName, 'borders');

        static::assertSame('3', $borderElements->getAttribute('count'), '3 borders in count attribute');
        static::assertSame(3, $borderElements->childNodes->length, '3 border childnodes present');

        /** @var \DOMElement $firstBorder */
        $firstBorder = $borderElements->childNodes->item(1); // 0  = default border
        $leftStyle = $firstBorder->getElementsByTagName('left')->item(0)->getAttribute('style');
        static::assertSame('medium', $leftStyle, 'Style is medium');

        /** @var \DOMElement $secondBorder */
        $secondBorder = $borderElements->childNodes->item(2);
        $rightStyle = $secondBorder->getElementsByTagName('right')->item(0)->getAttribute('style');
        static::assertSame('thick', $rightStyle, 'Style is thick');

        $styleXfsElements = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');

        // A rather relaxed test
        // Where a border is applied - the borderId attribute has to be greater than 0
        $bordersApplied = 0;
        /** @var \DOMElement $node */
        foreach ($styleXfsElements->childNodes as $node) {
            $shouldApplyBorder = (1 === (int) $node->getAttribute('applyBorder'));
            if ($shouldApplyBorder) {
                ++$bordersApplied;
                static::assertGreaterThan(0, (int) $node->getAttribute('borderId'), 'BorderId is greater than 0');
            } else {
                static::assertSame(0, (int) $node->getAttribute('borderId'), 'BorderId is 0');
            }
        }

        static::assertSame(3, $bordersApplied, 'Three borders have been applied');
    }

    /**
     * @param Row[]  $allRows
     * @param string $fileName
     *
     * @return Writer
     */
    private function writeToXLSXFile($allRows, $fileName)
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->setShouldUseInlineStrings(true);

        $writer->openToFile($resourcePath);
        $writer->addRows($allRows);
        $writer->close();

        return $writer;
    }

    /**
     * @param Row[]                                     $allRows
     * @param string                                    $fileName
     * @param null|\OpenSpout\Common\Entity\Style\Style $defaultStyle
     *
     * @return Writer
     */
    private function writeToXLSXFileWithDefaultStyle($allRows, $fileName, $defaultStyle)
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->setShouldUseInlineStrings(true);
        $writer->setDefaultRowStyle($defaultStyle);

        $writer->openToFile($resourcePath);
        $writer->addRows($allRows);
        $writer->close();

        return $writer;
    }

    /**
     * @param string $fileName
     * @param string $section
     *
     * @return \DOMElement
     */
    private function getXmlSectionFromStylesXmlFile($fileName, $section)
    {
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $xmlReader = new XMLReader();
        $xmlReader->openFileInZip($resourcePath, 'xl/styles.xml');
        $xmlReader->readUntilNodeFound($section);

        /** @var \DOMElement $xmlSection */
        $xmlSection = $xmlReader->expand();

        $xmlReader->close();

        return $xmlSection;
    }

    /**
     * @param string $fileName
     *
     * @return \DOMElement[]
     */
    private function getCellElementsFromSheetXmlFile($fileName)
    {
        $cellElements = [];

        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $xmlReader = new XMLReader();
        $xmlReader->openFileInZip($resourcePath, 'xl/worksheets/sheet1.xml');

        while ($xmlReader->read()) {
            if ($xmlReader->isPositionedOnStartingNode('c')) {
                /** @var \DOMElement $cellElement */
                $cellElement = $xmlReader->expand();
                $cellElements[] = $cellElement;
            }
        }

        $xmlReader->close();

        return $cellElements;
    }

    /**
     * @param string      $expectedValue
     * @param \DOMElement $parentElement
     * @param string      $childTagName
     * @param string      $attributeName
     */
    private function assertFirstChildHasAttributeEquals($expectedValue, $parentElement, $childTagName, $attributeName)
    {
        static::assertSame($expectedValue, $parentElement->getElementsByTagName($childTagName)->item(0)->getAttribute($attributeName));
    }

    /**
     * @param int         $expectedNumber
     * @param \DOMElement $parentElement
     * @param string      $message
     */
    private function assertChildrenNumEquals($expectedNumber, $parentElement, $message)
    {
        static::assertSame($expectedNumber, $parentElement->getElementsByTagName('*')->length, $message);
    }

    /**
     * @param \DOMElement $parentElement
     * @param string      $childTagName
     */
    private function assertChildExists($parentElement, $childTagName)
    {
        static::assertSame(1, $parentElement->getElementsByTagName($childTagName)->length);
    }
}
