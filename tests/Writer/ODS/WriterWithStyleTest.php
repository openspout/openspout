<?php

namespace OpenSpout\Writer\ODS;

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
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use OpenSpout\Writer\RowCreationHelper;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WriterWithStyleTest extends TestCase
{
    use RowCreationHelper;
    use TestUsingResource;

    /** @var Style */
    private $defaultStyle;

    protected function setUp(): void
    {
        $this->defaultStyle = (new StyleBuilder())->build();
    }

    public function testAddRowShouldThrowExceptionIfCallAddRowBeforeOpeningWriter()
    {
        $this->expectException(WriterNotOpenedException::class);

        $writer = WriterEntityFactory::createODSWriter();
        $writer->addRow($this->createStyledRowFromValues(['ods--11', 'ods--12'], $this->defaultStyle));
    }

    public function testAddRowShouldThrowExceptionIfCalledBeforeOpeningWriter()
    {
        $this->expectException(WriterNotOpenedException::class);

        $writer = WriterEntityFactory::createODSWriter();
        $writer->addRow($this->createStyledRowFromValues(['ods--11', 'ods--12'], $this->defaultStyle));
    }

    public function testAddRowShouldListAllUsedStylesInCreatedContentXmlFile()
    {
        $fileName = 'test_add_row_should_list_all_used_fonts.ods';

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
            ->setBackgroundColor(Color::GREEN)
            ->build()
        ;

        $dataRows = [
            $this->createStyledRowFromValues(['ods--11', 'ods--12'], $style),
            $this->createStyledRowFromValues(['ods--21', 'ods--22'], $style2),
        ];

        $this->writeToODSFile($dataRows, $fileName);

        $cellStyleElements = $this->getCellStyleElementsFromContentXmlFile($fileName);
        static::assertCount(3, $cellStyleElements, 'There should be 3 separate cell styles, including the default one.');

        // Second font should contain data from the first created style
        /** @var \DOMElement $customFont1Element */
        $customFont1Element = $cellStyleElements[1];
        $this->assertFirstChildHasAttributeEquals('bold', $customFont1Element, 'text-properties', 'fo:font-weight');
        $this->assertFirstChildHasAttributeEquals('italic', $customFont1Element, 'text-properties', 'fo:font-style');
        $this->assertFirstChildHasAttributeEquals('solid', $customFont1Element, 'text-properties', 'style:text-underline-style');
        $this->assertFirstChildHasAttributeEquals('solid', $customFont1Element, 'text-properties', 'style:text-line-through-style');

        // Third font should contain data from the second created style
        /** @var \DOMElement $customFont2Element */
        $customFont2Element = $cellStyleElements[2];
        $this->assertFirstChildHasAttributeEquals('15pt', $customFont2Element, 'text-properties', 'fo:font-size');
        $this->assertFirstChildHasAttributeEquals('#'.Color::RED, $customFont2Element, 'text-properties', 'fo:color');
        $this->assertFirstChildHasAttributeEquals('Cambria', $customFont2Element, 'text-properties', 'style:font-name');
        $this->assertFirstChildHasAttributeEquals('#'.Color::GREEN, $customFont2Element, 'table-cell-properties', 'fo:background-color');
    }

    public function testAddRowShouldWriteDefaultStyleSettings()
    {
        $fileName = 'test_add_row_should_write_default_style_settings.ods';
        $dataRow = $this->createStyledRowFromValues(['ods--11', 'ods--12'], $this->defaultStyle);

        $this->writeToODSFile([$dataRow], $fileName);

        $textPropertiesElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'style:text-properties');
        static::assertSame(Style::DEFAULT_FONT_SIZE.'pt', $textPropertiesElement->getAttribute('fo:font-size'));
        static::assertSame('#'.Style::DEFAULT_FONT_COLOR, $textPropertiesElement->getAttribute('fo:color'));
        static::assertSame(Style::DEFAULT_FONT_NAME, $textPropertiesElement->getAttribute('style:font-name'));
    }

    public function testAddRowShouldApplyStyleToCells()
    {
        $fileName = 'test_add_row_should_apply_style_to_cells.ods';

        $style = (new StyleBuilder())->setFontBold()->build();
        $style2 = (new StyleBuilder())->setFontSize(15)->build();
        $dataRows = [
            $this->createStyledRowFromValues(['ods--11'], $style),
            $this->createStyledRowFromValues(['ods--21'], $style2),
            $this->createRowFromValues(['ods--31']),
        ];

        $this->writeToODSFile($dataRows, $fileName);

        $cellDomElements = $this->getCellElementsFromContentXmlFile($fileName);
        static::assertCount(3, $cellDomElements, 'There should be 3 cells with content');

        static::assertSame('ce2', $cellDomElements[0]->getAttribute('table:style-name'));
        static::assertSame('ce3', $cellDomElements[1]->getAttribute('table:style-name'));
        static::assertSame('ce1', $cellDomElements[2]->getAttribute('table:style-name'));
    }

    public function testAddRowShouldReuseDuplicateStyles()
    {
        $fileName = 'test_add_row_should_reuse_duplicate_styles.ods';

        $style = (new StyleBuilder())->setFontBold()->build();
        $dataRows = $this->createStyledRowsFromValues([
            ['ods--11'],
            ['ods--21'],
        ], $style);

        $this->writeToODSFile($dataRows, $fileName);

        $cellDomElements = $this->getCellElementsFromContentXmlFile($fileName);
        static::assertCount(2, $cellDomElements, 'There should be 2 cells with content');

        static::assertSame('ce2', $cellDomElements[0]->getAttribute('table:style-name'));
        static::assertSame('ce2', $cellDomElements[1]->getAttribute('table:style-name'));
    }

    public function testAddRowShouldAddWrapTextAlignmentInfoInStylesXmlFileIfSpecified()
    {
        $fileName = 'test_add_row_should_add_wrap_text_alignment.ods';

        $style = (new StyleBuilder())->setShouldWrapText()->build();
        $dataRows = $this->createStyledRowsFromValues([
            ['ods--11', 'ods--12'],
        ], $style);

        $this->writeToODSFile($dataRows, $fileName);

        $styleElements = $this->getCellStyleElementsFromContentXmlFile($fileName);
        static::assertCount(2, $styleElements, 'There should be 2 styles (default and custom)');

        $customStyleElement = $styleElements[1];
        $this->assertFirstChildHasAttributeEquals('wrap', $customStyleElement, 'table-cell-properties', 'fo:wrap-option');
    }

    public function testAddRowShouldApplyWrapTextIfCellContainsNewLine()
    {
        $fileName = 'test_add_row_should_apply_wrap_text_if_new_lines.ods';
        $dataRows = $this->createStyledRowsFromValues([
            ["ods--11\nods--11"],
        ], $this->defaultStyle);

        $this->writeToODSFile($dataRows, $fileName);

        $styleElements = $this->getCellStyleElementsFromContentXmlFile($fileName);
        static::assertCount(2, $styleElements, 'There should be 2 styles (default and custom)');

        $customStyleElement = $styleElements[1];
        $this->assertFirstChildHasAttributeEquals('wrap', $customStyleElement, 'table-cell-properties', 'fo:wrap-option');
    }

    public function testAddRowShouldApplyCellAlignment()
    {
        $fileName = 'test_add_row_should_apply_cell_alignment.xlsx';

        $rightAlignedStyle = (new StyleBuilder())->setCellAlignment(CellAlignment::RIGHT)->build();
        $dataRows = $this->createStyledRowsFromValues([['ods--11']], $rightAlignedStyle);

        $this->writeToODSFile($dataRows, $fileName);

        $styleElements = $this->getCellStyleElementsFromContentXmlFile($fileName);
        static::assertCount(2, $styleElements, 'There should be 2 styles (default and custom)');

        $customStyleElement = $styleElements[1];
        $this->assertFirstChildHasAttributeEquals('end', $customStyleElement, 'paragraph-properties', 'fo:text-align');
    }

    public function testAddRowShouldSupportCellStyling()
    {
        $fileName = 'test_add_row_should_support_cell_styling.ods';

        $boldStyle = (new StyleBuilder())->setFontBold()->build();
        $underlineStyle = (new StyleBuilder())->setFontUnderline()->build();

        $dataRow = WriterEntityFactory::createRow([
            WriterEntityFactory::createCell('ods--11', $boldStyle),
            WriterEntityFactory::createCell('ods--12', $underlineStyle),
            WriterEntityFactory::createCell('ods--13', $underlineStyle),
        ]);

        $this->writeToODSFile([$dataRow], $fileName);

        $cellDomElements = $this->getCellElementsFromContentXmlFile($fileName);

        // First row should have 3 styled cells, with cell 2 and 3 sharing the same style
        static::assertSame('ce2', $cellDomElements[0]->getAttribute('table:style-name'));
        static::assertSame('ce3', $cellDomElements[1]->getAttribute('table:style-name'));
        static::assertSame('ce3', $cellDomElements[2]->getAttribute('table:style-name'));
    }

    public function testAddBackgroundColor()
    {
        $fileName = 'test_default_background_style.ods';

        $style = (new StyleBuilder())->setBackgroundColor(Color::WHITE)->build();
        $dataRows = $this->createStyledRowsFromValues([
            ['defaultBgColor'],
        ], $style);

        $this->writeToODSFile($dataRows, $fileName);

        $styleElements = $this->getCellStyleElementsFromContentXmlFile($fileName);
        static::assertCount(2, $styleElements, 'There should be 2 styles (default and custom)');

        $customStyleElement = $styleElements[1];
        $this->assertFirstChildHasAttributeEquals('#'.Color::WHITE, $customStyleElement, 'table-cell-properties', 'fo:background-color');
    }

    public function testBorders()
    {
        $fileName = 'test_borders.ods';

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

        $this->writeToODSFile($dataRows, $fileName);

        $styleElements = $this->getCellStyleElementsFromContentXmlFile($fileName);

        static::assertCount(3, $styleElements, 'There should be 3 styles)');

        // Use reflection for protected members here
        $widthMap = \ReflectionHelper::getStaticValue('OpenSpout\Writer\ODS\Helper\BorderHelper', 'widthMap');
        $styleMap = \ReflectionHelper::getStaticValue('OpenSpout\Writer\ODS\Helper\BorderHelper', 'styleMap');

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

        static::assertSame($expectedFirst, $actualFirst);

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

        static::assertSame($expectedThird, $actualThird);
    }

    public function testSetDefaultRowStyle()
    {
        $fileName = 'test_set_default_row_style.ods';

        $dataRows = $this->createRowsFromValues([
            ['ods--11'],
        ]);

        $defaultFontSize = 50;
        $defaultStyle = (new StyleBuilder())->setFontSize($defaultFontSize)->build();

        $this->writeToODSFileWithDefaultStyle($dataRows, $fileName, $defaultStyle);

        $textPropertiesElement = $this->getXmlSectionFromStylesXmlFile($fileName, 'style:text-properties');
        static::assertSame($defaultFontSize.'pt', $textPropertiesElement->getAttribute('fo:font-size'));
    }

    /**
     * @param Row[]  $allRows
     * @param string $fileName
     *
     * @return Writer
     */
    private function writeToODSFile($allRows, $fileName)
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createODSWriter();

        $writer->openToFile($resourcePath);
        $writer->addRows($allRows);
        $writer->close();

        return $writer;
    }

    /**
     * @param Row[]  $allRows
     * @param string $fileName
     * @param Style  $defaultStyle
     *
     * @return Writer
     */
    private function writeToODSFileWithDefaultStyle($allRows, $fileName, $defaultStyle)
    {
        $this->createGeneratedFolderIfNeeded($fileName);
        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $writer = WriterEntityFactory::createODSWriter();
        $writer->setDefaultRowStyle($defaultStyle);

        $writer->openToFile($resourcePath);
        $writer->addRows($allRows);
        $writer->close();

        return $writer;
    }

    /**
     * @param string $fileName
     *
     * @return \DOMElement[]
     */
    private function getCellElementsFromContentXmlFile($fileName)
    {
        $cellElements = [];

        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $xmlReader = new XMLReader();
        $xmlReader->openFileInZip($resourcePath, 'content.xml');

        while ($xmlReader->read()) {
            if ($xmlReader->isPositionedOnStartingNode('table:table-cell') && null !== $xmlReader->getAttribute('office:value-type')) {
                /** @var \DOMElement $cellElement */
                $cellElement = $xmlReader->expand();
                $cellElements[] = $cellElement;
            }
        }

        $xmlReader->close();

        return $cellElements;
    }

    /**
     * @param string $fileName
     *
     * @return \DOMElement[]
     */
    private function getCellStyleElementsFromContentXmlFile($fileName)
    {
        $cellStyleElements = [];

        $resourcePath = $this->getGeneratedResourcePath($fileName);

        $xmlReader = new XMLReader();
        $xmlReader->openFileInZip($resourcePath, 'content.xml');

        while ($xmlReader->read()) {
            if ($xmlReader->isPositionedOnStartingNode('style:style') && 'table-cell' === $xmlReader->getAttribute('style:family')) {
                /** @var \DOMElement $cellStyleElement */
                $cellStyleElement = $xmlReader->expand();
                $cellStyleElements[] = $cellStyleElement;
            }
        }

        $xmlReader->close();

        return $cellStyleElements;
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
        $xmlReader->openFileInZip($resourcePath, 'styles.xml');
        $xmlReader->readUntilNodeFound($section);

        $element = $xmlReader->expand();
        static::assertInstanceOf(\DOMElement::class, $element);

        return $element;
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
}
