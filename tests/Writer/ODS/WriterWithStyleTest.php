<?php

declare(strict_types=1);

namespace OpenSpout\Writer\ODS;

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
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use OpenSpout\Writer\ODS\Helper\BorderHelper;
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

        $writer->addRow(Row::fromValues(['ods--11', 'ods--12'], $this->defaultStyle));
    }

    public function testAddRowShouldThrowExceptionIfCalledBeforeOpeningWriter(): void
    {
        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $this->expectException(WriterNotOpenedException::class);

        $writer->addRow(Row::fromValues(['ods--11', 'ods--12'], $this->defaultStyle));
    }

    public function testAddRowShouldListAllUsedStylesInCreatedContentXmlFile(): void
    {
        $fileName = 'test_add_row_should_list_all_used_fonts.ods';

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
            ->setBackgroundColor(Color::GREEN)
        ;

        $dataRows = [
            Row::fromValues(['ods--11', 'ods--12'], $style),
            Row::fromValues(['ods--21', 'ods--22'], $style2),
        ];

        $this->writeToODSFile($dataRows, $fileName);

        $cellStyleElements = $this->getCellStyleElementsFromContentXmlFile($fileName);
        self::assertCount(3, $cellStyleElements, 'There should be 3 separate cell styles, including the default one.');

        // Second font should contain data from the first created style
        /** @var DOMElement $customFont1Element */
        $customFont1Element = $cellStyleElements[1];
        $this->assertFirstChildHasAttributeEquals('bold', $customFont1Element, 'text-properties', 'fo:font-weight');
        $this->assertFirstChildHasAttributeEquals('italic', $customFont1Element, 'text-properties', 'fo:font-style');
        $this->assertFirstChildHasAttributeEquals('solid', $customFont1Element, 'text-properties', 'style:text-underline-style');
        $this->assertFirstChildHasAttributeEquals('solid', $customFont1Element, 'text-properties', 'style:text-line-through-style');

        // Third font should contain data from the second created style
        /** @var DOMElement $customFont2Element */
        $customFont2Element = $cellStyleElements[2];
        $this->assertFirstChildHasAttributeEquals('15pt', $customFont2Element, 'text-properties', 'fo:font-size');
        $this->assertFirstChildHasAttributeEquals('#'.Color::RED, $customFont2Element, 'text-properties', 'fo:color');
        $this->assertFirstChildHasAttributeEquals('Cambria', $customFont2Element, 'text-properties', 'style:font-name');
        $this->assertFirstChildHasAttributeEquals('#'.Color::GREEN, $customFont2Element, 'table-cell-properties', 'fo:background-color');
    }

    public function testAddRowShouldWriteDefaultStyleSettings(): void
    {
        $fileName = 'test_add_row_should_write_default_style_settings.ods';
        $dataRow = Row::fromValues(['ods--11', 'ods--12'], $this->defaultStyle);

        $this->writeToODSFile([$dataRow], $fileName);

        $textPropertiesElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'style:text-properties');
        self::assertSame(Style::DEFAULT_FONT_SIZE.'pt', $textPropertiesElement->getAttribute('fo:font-size'));
        self::assertSame('#'.Style::DEFAULT_FONT_COLOR, $textPropertiesElement->getAttribute('fo:color'));
        self::assertSame(Style::DEFAULT_FONT_NAME, $textPropertiesElement->getAttribute('style:font-name'));
    }

    public function testAddRowShouldApplyStyleToCells(): void
    {
        $fileName = 'test_add_row_should_apply_style_to_cells.ods';

        $style = (new Style())->setFontBold();
        $style2 = (new Style())->setFontSize(15);
        $dataRows = [
            Row::fromValues(['ods--11'], $style),
            Row::fromValues(['ods--21'], $style2),
            Row::fromValues(['ods--31']),
        ];

        $this->writeToODSFile($dataRows, $fileName);

        $cellDomElements = $this->getCellElementsFromContentXmlFile($fileName);
        self::assertCount(3, $cellDomElements, 'There should be 3 cells with content');

        self::assertSame('ce2', $cellDomElements[0]->getAttribute('table:style-name'));
        self::assertSame('ce3', $cellDomElements[1]->getAttribute('table:style-name'));
        self::assertSame('ce1', $cellDomElements[2]->getAttribute('table:style-name'));
    }

    public function testAddRowShouldReuseDuplicateStyles(): void
    {
        $fileName = 'test_add_row_should_reuse_duplicate_styles.ods';

        $style = (new Style())->setFontBold();
        $dataRows = $this->createStyledRowsFromValues([
            ['ods--11'],
            ['ods--21'],
        ], $style);

        $this->writeToODSFile($dataRows, $fileName);

        $cellDomElements = $this->getCellElementsFromContentXmlFile($fileName);
        self::assertCount(2, $cellDomElements, 'There should be 2 cells with content');

        self::assertSame('ce2', $cellDomElements[0]->getAttribute('table:style-name'));
        self::assertSame('ce2', $cellDomElements[1]->getAttribute('table:style-name'));
    }

    public function testAddRowShouldAddWrapTextAlignmentInfoInStylesXmlFileIfSpecified(): void
    {
        $fileName = 'test_add_row_should_add_wrap_text_alignment.ods';

        $style = (new Style())->setShouldWrapText();
        $dataRows = $this->createStyledRowsFromValues([
            ['ods--11', 'ods--12'],
        ], $style);

        $this->writeToODSFile($dataRows, $fileName);

        $styleElements = $this->getCellStyleElementsFromContentXmlFile($fileName);
        self::assertCount(2, $styleElements, 'There should be 2 styles (default and custom)');

        $customStyleElement = $styleElements[1];
        $this->assertFirstChildHasAttributeEquals('wrap', $customStyleElement, 'table-cell-properties', 'fo:wrap-option');
    }

    public function testAddRowShouldAddNegatedWrapTextAlignmentInfoInStylesXmlFileIfSpecified(): void
    {
        $fileName = 'test_add_row_should_add_negated_wrap_text_alignment.ods';

        $style = (new Style())->setShouldWrapText(false);
        $dataRows = $this->createStyledRowsFromValues([
            ['ods--11', 'ods--12'],
        ], $style);

        $this->writeToODSFile($dataRows, $fileName);

        $styleElements = $this->getCellStyleElementsFromContentXmlFile($fileName);
        self::assertCount(2, $styleElements, 'There should be 2 styles (default and custom)');

        $customStyleElement = $styleElements[1];
        $this->assertFirstChildHasAttributeEquals('no-wrap', $customStyleElement, 'table-cell-properties', 'fo:wrap-option');
    }

    public function testAddRowShouldApplyWrapTextIfCellContainsNewLine(): void
    {
        $fileName = 'test_add_row_should_apply_wrap_text_if_new_lines.ods';
        $dataRows = $this->createStyledRowsFromValues([
            ["ods--11\nods--11"],
        ], $this->defaultStyle);

        $this->writeToODSFile($dataRows, $fileName);

        $styleElements = $this->getCellStyleElementsFromContentXmlFile($fileName);
        self::assertCount(2, $styleElements, 'There should be 2 styles (default and custom)');

        $customStyleElement = $styleElements[1];
        $this->assertFirstChildHasAttributeEquals('wrap', $customStyleElement, 'table-cell-properties', 'fo:wrap-option');
    }

    public function testAddRowShouldApplyCellAlignment(): void
    {
        $fileName = 'test_add_row_should_apply_cell_alignment.xlsx';

        $dataRows = [];
        $rightAlignedStyle = (new Style())->setCellAlignment(CellAlignment::RIGHT);
        $dataRows[] = Row::fromValues(['ods--11'], $rightAlignedStyle);
        $leftAlignedStyle = (new Style())->setCellAlignment(CellAlignment::LEFT);
        $dataRows[] = Row::fromValues(['ods--12'], $leftAlignedStyle);

        $this->writeToODSFile($dataRows, $fileName);

        $styleElements = $this->getCellStyleElementsFromContentXmlFile($fileName);
        self::assertCount(3, $styleElements, 'There should be 3 styles (1 default and 2 custom)');

        $customStyleElement = $styleElements[1];
        $this->assertFirstChildHasAttributeEquals('end', $customStyleElement, 'paragraph-properties', 'fo:text-align');
    }

    public function testAddRowShouldApplyCellVerticalAlignment(): void
    {
        $fileName = 'test_add_row_should_apply_cell_alignment.xlsx';

        $dataRows = [];
        $rightAlignedStyle = (new Style())->setCellVerticalAlignment(CellVerticalAlignment::BASELINE);
        $dataRows[] = Row::fromValues(['ods--11'], $rightAlignedStyle);
        $leftAlignedStyle = (new Style())->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
        $dataRows[] = Row::fromValues(['ods--12'], $leftAlignedStyle);

        $this->writeToODSFile($dataRows, $fileName);

        $styleElements = $this->getCellStyleElementsFromContentXmlFile($fileName);
        self::assertCount(3, $styleElements, 'There should be 3 styles (1 default and 2 custom)');

        $customStyleElement = $styleElements[1];
        $this->assertFirstChildHasAttributeEquals('baseline', $customStyleElement, 'paragraph-properties', 'fo:vertical-align');
    }

    public function testAddRowShouldSupportCellStyling(): void
    {
        $fileName = 'test_add_row_should_support_cell_styling.ods';

        $boldStyle = (new Style())->setFontBold();
        $underlineStyle = (new Style())->setFontUnderline();

        $dataRow = new Row([
            Cell::fromValue('ods--11', $boldStyle),
            Cell::fromValue('ods--12', $underlineStyle),
            Cell::fromValue('ods--13', $underlineStyle),
        ]);

        $this->writeToODSFile([$dataRow], $fileName);

        $cellDomElements = $this->getCellElementsFromContentXmlFile($fileName);

        // First row should have 3 styled cells, with cell 2 and 3 sharing the same style
        self::assertSame('ce2', $cellDomElements[0]->getAttribute('table:style-name'));
        self::assertSame('ce3', $cellDomElements[1]->getAttribute('table:style-name'));
        self::assertSame('ce3', $cellDomElements[2]->getAttribute('table:style-name'));
    }

    public function testAddBackgroundColor(): void
    {
        $fileName = 'test_default_background_style.ods';

        $style = (new Style())->setBackgroundColor(Color::WHITE);
        $dataRows = $this->createStyledRowsFromValues([
            ['defaultBgColor'],
        ], $style);

        $this->writeToODSFile($dataRows, $fileName);

        $styleElements = $this->getCellStyleElementsFromContentXmlFile($fileName);
        self::assertCount(2, $styleElements, 'There should be 2 styles (default and custom)');

        $customStyleElement = $styleElements[1];
        $this->assertFirstChildHasAttributeEquals('#'.Color::WHITE, $customStyleElement, 'table-cell-properties', 'fo:background-color');
    }

    public function testBorders(): void
    {
        $fileName = 'test_borders.ods';

        $borderBottomGreenThickSolid = new Border(new BorderPart(Border::BOTTOM, Color::GREEN, Border::WIDTH_THICK, Border::STYLE_SOLID));
        $borderTopRedThinDashed = new Border(new BorderPart(Border::TOP, Color::RED, Border::WIDTH_THIN, Border::STYLE_DASHED));
        $borderLeft = new Border(new BorderPart(Border::LEFT, Color::BLACK, Border::WIDTH_MEDIUM, Border::STYLE_NONE));

        $styles = [
            (new Style())->setBorder($borderBottomGreenThickSolid),
            new Style(),
            (new Style())->setBorder($borderTopRedThinDashed),
            (new Style())->setBorder($borderLeft),
        ];

        $dataRows = [
            Row::fromValues(['row-with-border-bottom-green-thick-solid'], $styles[0]),
            Row::fromValues(['row-without-border'], $styles[1]),
            Row::fromValues(['row-with-border-top-red-thin-dashed'], $styles[2]),
            Row::fromValues(['row-with-border-left'], $styles[3]),
        ];

        $this->writeToODSFile($dataRows, $fileName);

        $styleElements = $this->getCellStyleElementsFromContentXmlFile($fileName);

        self::assertCount(4, $styleElements);

        // Use reflection for protected members here
        $widthMap = BorderHelper::widthMap;
        $styleMap = BorderHelper::styleMap;

        $expectedFirst = sprintf(
            '%s %s #%s',
            $widthMap[Border::WIDTH_THICK],
            $styleMap[Border::STYLE_SOLID],
            Color::GREEN
        );

        $actualFirst = $styleElements[1]
            ->getElementsByTagName('table-cell-properties')
            ->item(0)
            ->getAttribute('fo:border-bottom')
        ;

        self::assertSame($expectedFirst, $actualFirst);

        $expectedThird = sprintf(
            '%s %s #%s',
            $widthMap[Border::WIDTH_THIN],
            $styleMap[Border::STYLE_DASHED],
            Color::RED
        );

        $actualThird = $styleElements[2]
            ->getElementsByTagName('table-cell-properties')
            ->item(0)
            ->getAttribute('fo:border-top')
        ;

        self::assertSame($expectedThird, $actualThird);
    }

    public function testSetDefaultRowStyle(): void
    {
        $fileName = 'test_set_default_row_style.ods';

        $dataRows = $this->createRowsFromValues([
            ['ods--11'],
        ]);

        $defaultFontSize = 50;
        $defaultStyle = (new Style())->setFontSize($defaultFontSize);

        $this->writeToODSFileWithDefaultStyle($dataRows, $fileName, $defaultStyle);

        $textPropertiesElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'style:text-properties');
        self::assertSame($defaultFontSize.'pt', $textPropertiesElement->getAttribute('fo:font-size'));
    }

    /**
     * @param Row[] $allRows
     */
    private function writeToODSFile(array $allRows, string $fileName): Writer
    {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);

        $writer->openToFile($resourcePath);
        $writer->addRows($allRows);
        $writer->close();

        return $writer;
    }

    /**
     * @param Row[] $allRows
     */
    private function writeToODSFileWithDefaultStyle(array $allRows, string $fileName, Style $defaultStyle): Writer
    {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());
        $options->DEFAULT_ROW_STYLE = $defaultStyle;
        $writer = new Writer($options);

        $writer->openToFile($resourcePath);
        $writer->addRows($allRows);
        $writer->close();

        return $writer;
    }

    /**
     * @return DOMElement[]
     */
    private function getCellElementsFromContentXmlFile(string $fileName): array
    {
        $cellElements = [];

        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $xmlReader = new XMLReader();
        $xmlReader->openFileInZip($resourcePath, 'content.xml');

        while ($xmlReader->read()) {
            if ($xmlReader->isPositionedOnStartingNode('table:table-cell') && null !== $xmlReader->getAttribute('office:value-type')) {
                /** @var DOMElement $cellElement */
                $cellElement = $xmlReader->expand();
                $cellElements[] = $cellElement;
            }
        }

        $xmlReader->close();

        return $cellElements;
    }

    /**
     * @return DOMElement[]
     */
    private function getCellStyleElementsFromContentXmlFile(string $fileName): array
    {
        $cellStyleElements = [];

        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $xmlReader = new XMLReader();
        $xmlReader->openFileInZip($resourcePath, 'content.xml');

        while ($xmlReader->read()) {
            if ($xmlReader->isPositionedOnStartingNode('style:style') && 'table-cell' === $xmlReader->getAttribute('style:family')) {
                /** @var DOMElement $cellStyleElement */
                $cellStyleElement = $xmlReader->expand();
                $cellStyleElements[] = $cellStyleElement;
            }
        }

        $xmlReader->close();

        return $cellStyleElements;
    }

    private function getXmlSectionFromStylesXmlFile(string $fileName, string $section): DOMElement
    {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $xmlReader = new XMLReader();
        $xmlReader->openFileInZip($resourcePath, 'styles.xml');
        $xmlReader->readUntilNodeFound($section);

        $element = $xmlReader->expand();
        self::assertInstanceOf(DOMElement::class, $element);

        return $element;
    }

    private function assertFirstChildHasAttributeEquals(string $expectedValue, DOMElement $parentElement, string $childTagName, string $attributeName): void
    {
        self::assertSame($expectedValue, $parentElement->getElementsByTagName($childTagName)->item(0)->getAttribute($attributeName));
    }
}
