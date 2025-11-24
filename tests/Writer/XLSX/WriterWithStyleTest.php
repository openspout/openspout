<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

use DOMElement;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderName;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\BorderStyle;
use OpenSpout\Common\Entity\Style\BorderWidth;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Reader\Wrapper\XMLReader;
use OpenSpout\TestUsingResource;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WriterWithStyleTest extends TestCase
{
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
        $writer->addRow(Row::fromValues(['xlsx--11', 'xlsx--12']));
    }

    public function testAddRowShouldListAllUsedFontsInCreatedStylesXmlFile(): void
    {
        $fileName = 'test_add_row_should_list_all_used_fonts.xlsx';

        $style = new Style(
            fontBold: true,
            fontItalic: true,
            fontUnderline: true,
            fontStrikethrough: true,
        );
        $style2 = new Style(
            fontSize: 15,
            fontColor: Color::RED,
            fontName: 'Cambria',
        );

        $dataRows = [
            Row::fromValuesWithStyles(['xlsx--11', 'xlsx--12'], [$style, $style]),
            Row::fromValuesWithStyles(['xlsx--21', 'xlsx--22'], [$style2, $style2]),
            Row::fromValues(['xlsx--31', 'xlsx--2']),
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
        $this->assertFirstChildHasAttributeEquals((string) Style::DEFAULT_FONT_SIZE, $secondFontElement, 'sz', 'val');
        $this->assertFirstChildHasAttributeEquals(Color::toARGB(Style::DEFAULT_FONT_COLOR), $secondFontElement, 'color', 'rgb');
        $this->assertFirstChildHasAttributeEquals(Style::DEFAULT_FONT_NAME, $secondFontElement, 'name', 'val');

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

        $style = new Style(fontBold: true);
        $style2 = new Style(fontSize: 15);

        $dataRows = [
            Row::fromValuesWithStyles(['xlsx--11'], [$style]),
            Row::fromValuesWithStyles(['xlsx--21'], [$style2]),
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

        $styleWithFont = new Style(fontBold: true);
        $styleWithBackground = new Style(backgroundColor: Color::BLUE);

        $border = new Border(new BorderPart(BorderName::BOTTOM, Color::GREEN));
        $styleWithBorder = new Style(border: $border);

        $dataRows = [
            Row::fromValues(['xlsx--11', '', 'xlsx--13']),
            Row::fromValuesWithStyles(['xlsx--21', '', 'xlsx--23'], [$styleWithFont, $styleWithFont, $styleWithFont]),
            Row::fromValuesWithStyles(['xlsx--31', '', 'xlsx--33'], [$styleWithBackground, $styleWithBackground, $styleWithBackground]),
            Row::fromValuesWithStyles(['xlsx--41', '', 'xlsx--43'], [$styleWithBorder, $styleWithBorder, $styleWithBorder]),
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

        $style = new Style(fontBold: true);
        $dataRows = [
            Row::fromValuesWithStyles(['xlsx--11'], [$style]),
            Row::fromValuesWithStyles(['xlsx--21'], [$style]),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellDomElements = $this->getCellElementsFromSheetXmlFile($fileName);
        self::assertSame('1', $cellDomElements[0]->getAttribute('s'));
        self::assertSame('1', $cellDomElements[1]->getAttribute('s'));
    }

    public function testAddRowWithNumFmtStyles(): void
    {
        $fileName = 'test_add_row_with_numfmt.xlsx';
        $style = new Style(
            fontBold: true,
            format: '0.00',
        );
        $style2 = new Style(
            fontBold: true,
            format: '0.000',
        );

        $dataRows = [
            Row::fromValuesWithStyles([1.123456789], [$style]),
            Row::fromValuesWithStyles([12.1], [$style2]),
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

        $style = new Style(shouldWrapText: true);
        $dataRows = [
            Row::fromValuesWithStyles(['xlsx--11', 'xlsx--12'], [$style, $style]),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellXfsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        $xfElement = $cellXfsDomElement->getElementsByTagName('xf')->item(1);
        self::assertSame('1', $xfElement->getAttribute('applyAlignment'));
        $this->assertFirstChildHasAttributeEquals('1', $xfElement, 'alignment', 'wrapText');
    }

    public function testAddRowShouldAddNegatedWrapTextAlignmentInfoInStylesXmlFileIfSpecified(): void
    {
        $fileName = 'test_add_row_should_add_negated_wrap_text_alignment.xlsx';

        $style = new Style(shouldWrapText: false);
        $dataRows = [
            Row::fromValuesWithStyles(['xlsx--11', 'xlsx--12'], [$style, $style]),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellXfsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        $xfElement = $cellXfsDomElement->getElementsByTagName('xf')->item(1);
        self::assertSame('1', $xfElement->getAttribute('applyAlignment'));
        $this->assertFirstChildHasAttributeEquals('0', $xfElement, 'alignment', 'wrapText');
    }

    public function testAddRowShouldApplyWrapTextIfCellContainsNewLine(): void
    {
        $fileName = 'test_add_row_should_apply_wrap_text_if_new_lines.xlsx';

        $dataRows = [
            Row::fromValues(["xlsx--11\nxlsx--11"]),
            Row::fromValues(['xlsx--21']),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellXfsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        $xfElement = $cellXfsDomElement->getElementsByTagName('xf')->item(1);
        self::assertSame('1', $xfElement?->getAttribute('applyAlignment'));
        $this->assertFirstChildHasAttributeEquals('1', $xfElement, 'alignment', 'wrapText');
    }

    public function testAddRowShouldApplyCellAlignment(): void
    {
        $fileName = 'test_add_row_should_apply_cell_alignment.xlsx';

        $rightAlignedStyle = new Style(cellAlignment: CellAlignment::RIGHT);
        $dataRows = [
            Row::fromValuesWithStyles(['xlsx--11'], [$rightAlignedStyle]),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellXfsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        $xfElement = $cellXfsDomElement->getElementsByTagName('xf')->item(1);
        self::assertSame('1', $xfElement->getAttribute('applyAlignment'));
        $this->assertFirstChildHasAttributeEquals(CellAlignment::RIGHT->value, $xfElement, 'alignment', 'horizontal');
    }

    public function testAddRowShouldApplyCellVerticalAlignment(): void
    {
        $fileName = 'test_add_row_should_apply_cell_alignment.xlsx';

        $rightAlignedStyle = new Style(cellVerticalAlignment: CellVerticalAlignment::JUSTIFY);
        $dataRows = [
            Row::fromValuesWithStyles(['xlsx--11'], [$rightAlignedStyle]),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellXfsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        $xfElement = $cellXfsDomElement->getElementsByTagName('xf')->item(1);
        self::assertSame('1', $xfElement->getAttribute('applyAlignment'));
        $this->assertFirstChildHasAttributeEquals(CellAlignment::JUSTIFY->value, $xfElement, 'alignment', 'vertical');
    }

    public function testAddRowShouldApplyShrinkToFit(): void
    {
        $fileName = 'test_add_row_should_apply_shrink_to_fit.xlsx';

        $shrinkToFitStyle = new Style(shouldShrinkToFit: true);
        $dataRows = [
            Row::fromValuesWithStyles(['xlsx--11'], [$shrinkToFitStyle]),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $cellXfsDomElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        $xfElement = $cellXfsDomElement->getElementsByTagName('xf')->item(1);
        self::assertSame('1', $xfElement->getAttribute('applyAlignment'));
        $this->assertFirstChildHasAttributeEquals('true', $xfElement, 'alignment', 'shrinkToFit');
    }

    public function testAddRowShouldSupportCellStyling(): void
    {
        $fileName = 'test_add_row_should_support_cell_styling.xlsx';

        $boldStyle = new Style(fontBold: true);
        $underlineStyle = new Style(fontUnderline: true);

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

        $style = new Style(backgroundColor: Color::WHITE);
        $dataRows = [
            Row::fromValuesWithStyles(['BgColor'], [$style]),
        ];

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

        $style = new Style(
            fontBold: true,
            backgroundColor: Color::RED,
        );
        $style2 = new Style(
            backgroundColor: Color::RED,
        );

        $dataRows = [
            Row::fromValuesWithStyles(['row-bold-background-red'], [$style]),
            Row::fromValuesWithStyles(['row-background-red'], [$style2]),
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

        $borderBottomGreenThickSolid = new Border(new BorderPart(BorderName::BOTTOM, Color::GREEN, BorderWidth::THICK, BorderStyle::SOLID));
        $borderTopRedThinDashed = new Border(new BorderPart(BorderName::TOP, Color::RED, BorderWidth::THIN, BorderStyle::DASHED));

        $styles = [
            new Style(border: $borderBottomGreenThickSolid),
            new Style(),
            new Style(border: $borderTopRedThinDashed),
        ];

        $dataRows = [
            Row::fromValuesWithStyles(['row-with-border-bottom-green-thick-solid'], [$styles[0]]),
            Row::fromValuesWithStyles(['row-without-border'], [$styles[1]]),
            Row::fromValuesWithStyles(['row-with-border-top-red-thin-dashed'], [$styles[2]]),
        ];

        $this->writeToXLSXFile($dataRows, $fileName);

        $borderElements = $this->getXmlSectionFromStylesXmlFile($fileName, 'borders');
        self::assertSame('3', $borderElements->getAttribute('count'), '3 borders present');

        $styleXfsElements = $this->getXmlSectionFromStylesXmlFile($fileName, 'cellXfs');
        self::assertSame('4', $styleXfsElements->getAttribute('count'), '3 cell xfs present');
    }

    public function testBordersCorrectOrder(): void
    {
        // Border should be Left, Right, Top, Bottom
        $fileName = 'test_borders_correct_order.xlsx';

        $borders = new Border(
            new BorderPart(BorderName::RIGHT),
            new BorderPart(BorderName::TOP),
            new BorderPart(BorderName::LEFT),
            new BorderPart(BorderName::BOTTOM)
        );

        $style = new Style(border: $borders);
        $dataRows = [
            Row::fromValuesWithStyles(['I am a teapot'], [$style]),
        ];

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
        $dataRows = [
            Row::fromValues(['xlsx--11']),
        ];

        $defaultFontSize = 50;
        $defaultStyle = new Style(fontSize: $defaultFontSize);

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

        $borderLeft = new Border(new BorderPart(BorderName::LEFT));
        $borderLeftStyle = new Style(border: $borderLeft);

        $borderRight = new Border(new BorderPart(BorderName::RIGHT, Color::RED, BorderWidth::THICK));
        $borderRightStyle = new Style(border: $borderRight);

        $fontStyle = new Style(fontBold: true);
        $emptyStyle = new Style();

        $borderRightFontBoldStyle = $borderRightStyle->withFontBold(true);

        $dataRows = [
            Row::fromValuesWithStyles(['Border-Left'], [$borderLeftStyle]),
            Row::fromValuesWithStyles(['Empty'], [$emptyStyle]),
            Row::fromValuesWithStyles(['Font-Bold'], [$fontStyle]),
            Row::fromValuesWithStyles(['Border-Right'], [$borderRightStyle]),
            Row::fromValuesWithStyles(['Border-Right-Font-Bold'], [$borderRightFontBoldStyle]),
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

        $options = new Options(
            tempFolder: (new TestUsingResource())->getTempFolderPath(),
            SHOULD_USE_INLINE_STRINGS: true,
        );
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

        $options = new Options(
            FALLBACK_STYLE: $defaultStyle,
            tempFolder: (new TestUsingResource())->getTempFolderPath(),
            SHOULD_USE_INLINE_STRINGS: true,
        );
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
