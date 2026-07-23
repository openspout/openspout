<?php

declare(strict_types=1);

namespace OpenSpout\Writer\ODS;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Reader\Exception\XMLProcessingException;
use OpenSpout\Reader\Wrapper\XMLReader;
use OpenSpout\TestUsingResource;
use OpenSpout\Writer\Common\Entity\Sheet;
use OpenSpout\Writer\Exception\InvalidSheetNameException;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SheetTest extends TestCase
{
    public function testGetSheetIndex(): void
    {
        $sheets = $this->writeDataToMulitpleSheetsAndReturnSheets('test_get_sheet_index.ods');

        self::assertCount(2, $sheets, '2 sheets should have been created');
        self::assertSame(0, $sheets[0]->getIndex(), 'The first sheet should be index 0');
        self::assertSame(1, $sheets[1]->getIndex(), 'The second sheet should be index 1');
    }

    public function testGetSheetName(): void
    {
        $sheets = $this->writeDataToMulitpleSheetsAndReturnSheets('test_get_sheet_name.ods');

        self::assertCount(2, $sheets, '2 sheets should have been created');
        self::assertSame('Sheet1', $sheets[0]->getName(), 'Invalid name for the first sheet');
        self::assertSame('Sheet2', $sheets[1]->getName(), 'Invalid name for the second sheet');
    }

    public function testSetSheetNameShouldCreateSheetWithCustomName(): void
    {
        $fileName = 'test_set_name_should_create_sheet_with_custom_name.ods';
        $customSheetName = 'CustomName';
        $this->writeDataAndReturnSheetWithCustomName($fileName, $customSheetName);

        $this->assertSheetNameEquals($customSheetName, $fileName, "The sheet name should have been changed to '{$customSheetName}'");
    }

    public function testSetSheetNameShouldThrowWhenNameIsAlreadyUsed(): void
    {
        $this->expectException(InvalidSheetNameException::class);

        $fileName = 'test_set_name_with_non_unique_name.ods';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
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
        $fileName = 'test_set_visibility_should_create_sheet_hidden.ods';
        $this->writeDataToHiddenSheet($fileName);

        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $pathToContentFile = $resourcePath.'#content.xml';
        $xmlContents = file_get_contents('zip://'.$pathToContentFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString(' table:display="false"', $xmlContents, 'The sheet visibility should have been changed to "hidden"');
    }

    public function testWritesColumnWidths(): void
    {
        $fileName = 'test_column_widths.ods';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->addRow(Row::fromValues(['ods--11', 'ods--12']));
        $options->setColumnWidth(100.0, 1);
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#content.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('style:column-width="100pt"', $xmlContents, 'No cols tag found in sheet');
        self::assertStringContainsString('table:number-columns-repeated="1"', $xmlContents, 'No expected column width definition found in sheet');
    }

    public function testWritesMultipleColumnWidthsInRanges(): void
    {
        $fileName = 'test_multiple_column_widths_in_ranges.ods';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->addRow(Row::fromValues(['ods--11', 'ods--12', 'ods--13', 'ods--14', 'ods--15', 'ods--16']));
        $options->setColumnWidth(50.0, 1, 3, 4, 6);
        $options->setColumnWidth(100.0, 2, 5);
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#content.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('style:column-width="50pt"', $xmlContents, 'No cols tag found in sheet');
        self::assertStringContainsString('style:column-width="100pt"', $xmlContents, 'No cols tag found in sheet');
        self::assertStringContainsString('table:number-columns-repeated="1"', $xmlContents, 'No expected column width definition found in sheet');
        self::assertStringContainsString('table:number-columns-repeated="2"', $xmlContents, 'No expected column width definition found in sheet');
    }

    public function testWritesHiddenAndCollapsedColumnsAsCollapsedVisibility(): void
    {
        $fileName = 'test_column_hidden_collapsed.ods';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->addRow(Row::fromValues(['ods--11', 'ods--12', 'ods--13']));
        $sheet = $writer->getCurrentSheet();
        $sheet->setColumnHidden(true, 1);
        $sheet->setColumnCollapsed(true, 3);
        $writer->close();

        $pathToWorkbookFile = $resourcePath.'#content.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString('table:visibility="collapse"', $xmlContents, 'Hidden/collapsed columns should be written with collapse visibility');
        self::assertStringContainsString('<table:table-column table:default-cell-style-name="ce1" table:style-name="default-column-style" table:visibility="collapse" table:number-columns-repeated="1"/>', $xmlContents, 'No expected collapsed column definition found in sheet');
    }

    /**
     * @param array<array-key, array{start: positive-int, end: positive-int, width: float}> $rules
     * @param array<array-key, array{repeated: string, style: string, width?: string}>      $expected
     *
     * @throws IOException
     * @throws XMLProcessingException
     * @throws WriterNotOpenedException
     */
    #[DataProvider('provideWritesColumnWidthsInVariousScenariosCases')]
    public function testWritesColumnWidthsInVariousScenarios(array $rules, array $expected): void
    {
        $fileName = 'test_column_widths_in_various_scensarios.ods';
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);
        $writer->addRow(Row::fromValues(['ods--11', 'ods--12', 'ods--13', 'ods--14', 'ods--15', 'ods--16']));
        foreach ($rules as $rule) {
            $options->setColumnWidthForRange(...$rule);
        }
        $writer->close();

        $reader = new XMLReader();
        $reader->openFileInZip($resourcePath, 'content.xml');

        $styles = [];
        $columns = [];

        while ($reader->read()) {
            if (XMLReader::ELEMENT !== $reader->nodeType) {
                continue;
            }
            if ('style:style' === $reader->name
                && 'table-column' === $reader->getAttribute('style:family')
                && str_starts_with($styleName = $reader->getAttribute('style:name'), 'co')
            ) {
                $styles[$styleName] = $reader->readInnerXml();
            } elseif ('table:table-column' === $reader->name) {
                $columns[] = [
                    'repeated' => $reader->getAttribute('table:number-columns-repeated'),
                    'style' => $reader->getAttribute('table:style-name'),
                ];
            }
        }

        self::assertCount(\count($expected), $columns);

        foreach ($expected as $index => $expectedAttributes) {
            self::assertSame($expectedAttributes['repeated'], $columns[$index]['repeated']);
            self::assertSame($expectedAttributes['style'], $columns[$index]['style']);
            if (isset($expectedAttributes['width'])) {
                self::assertStringContainsString(
                    \sprintf('style:column-width="%s"', $expectedAttributes['width']),
                    $styles[$expectedAttributes['style']]
                );
            }
        }
    }

    public static function provideWritesColumnWidthsInVariousScenariosCases(): iterable
    {
        return [
            'partial overlap' => [
                [
                    ['start' => 1, 'end' => 10, 'width' => 50.0],
                    ['start' => 5, 'end' => 7, 'width' => 100.0],
                ],
                [
                    ['repeated' => '4', 'style' => 'co0', 'width' => '50pt'],
                    ['repeated' => '3', 'style' => 'co1', 'width' => '100pt'],
                    ['repeated' => '3', 'style' => 'co2', 'width' => '50pt'],
                ],
            ],

            'nested overlap' => [
                [
                    ['start' => 1, 'end' => 10, 'width' => 50.0],
                    ['start' => 2, 'end' => 8, 'width' => 100.0],
                    ['start' => 4, 'end' => 6, 'width' => 80.0],
                ],
                [
                    ['repeated' => '1', 'style' => 'co0', 'width' => '50pt'],
                    ['repeated' => '2', 'style' => 'co1', 'width' => '100pt'],
                    ['repeated' => '3', 'style' => 'co2', 'width' => '80pt'],
                    ['repeated' => '2', 'style' => 'co3', 'width' => '100pt'],
                    ['repeated' => '2', 'style' => 'co4', 'width' => '50pt'],
                ],
            ],

            'no overlap' => [
                [
                    ['start' => 1, 'end' => 3, 'width' => 50.0],
                    ['start' => 5, 'end' => 7, 'width' => 100.0],
                ],
                [
                    ['repeated' => '3', 'style' => 'co0', 'width' => '50pt'],
                    ['repeated' => '1', 'style' => 'default-column-style'],
                    ['repeated' => '3', 'style' => 'co1', 'width' => '100pt'],
                ],
            ],

            'identical intervals' => [
                [
                    ['start' => 1, 'end' => 10, 'width' => 50.0],
                    ['start' => 1, 'end' => 10, 'width' => 100.0],
                ],
                [
                    ['repeated' => '10', 'style' => 'co0', 'width' => '100pt'],
                ],
            ],

            'laster interval contains previous' => [
                [
                    ['start' => 5, 'end' => 7, 'width' => 100.0],
                    ['start' => 1, 'end' => 10, 'width' => 50.0],
                ],
                [
                    ['repeated' => '10', 'style' => 'co0', 'width' => '50pt'],
                ],
            ],

            'adjacent intervals' => [
                [
                    ['start' => 1, 'end' => 5, 'width' => 50.0],
                    ['start' => 6, 'end' => 10, 'width' => 100.0],
                ],
                [
                    ['repeated' => '5', 'style' => 'co0', 'width' => '50pt'],
                    ['repeated' => '5', 'style' => 'co1', 'width' => '100pt'],
                ],
            ],

            'boundary overlap' => [
                [
                    ['start' => 1, 'end' => 5, 'width' => 50.0],
                    ['start' => 5, 'end' => 10, 'width' => 100.0],
                ],
                [
                    ['repeated' => '4', 'style' => 'co0', 'width' => '50pt'],
                    ['repeated' => '6', 'style' => 'co1', 'width' => '100pt'],
                ],
            ],

            'single point interval' => [
                [
                    ['start' => 5, 'end' => 5, 'width' => 50.0],
                ],
                [
                    ['repeated' => '4', 'style' => 'default-column-style'],
                    ['repeated' => '1', 'style' => 'co0', 'width' => '50pt'],
                    ['repeated' => '1', 'style' => 'default-column-style'],
                ],
            ],
        ];
    }

    private function writerForFile(string $fileName): Writer
    {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        return $writer;
    }

    private function writeDataAndReturnSheetWithCustomName(string $fileName, string $sheetName): void
    {
        $writer = $this->writerForFile($fileName);

        $sheet = $writer->getCurrentSheet();
        $sheet->setName($sheetName);

        $writer->addRow(Row::fromValues(['ods--11', 'ods--12']));
        $writer->close();
    }

    /**
     * @return Sheet[]
     */
    private function writeDataToMulitpleSheetsAndReturnSheets(string $fileName): array
    {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $writer->addRow(Row::fromValues(['ods--sheet1--11', 'ods--sheet1--12']));
        $writer->addNewSheetAndMakeItCurrent();
        $writer->addRow(Row::fromValues(['ods--sheet2--11', 'ods--sheet2--12', 'ods--sheet2--13']));

        $writer->close();

        return $writer->getSheets();
    }

    private function writeDataToHiddenSheet(string $fileName): void
    {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $options = new Options(tempFolder: (new TestUsingResource())->getTempFolderPath());
        $writer = new Writer($options);
        $writer->openToFile($resourcePath);

        $sheet = $writer->getCurrentSheet();
        $sheet->setIsVisible(false);

        $writer->addRow(Row::fromValues(['ods--11', 'ods--12']));
        $writer->close();
    }

    private function assertSheetNameEquals(string $expectedName, string $fileName, string $message = ''): void
    {
        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);
        $pathToWorkbookFile = $resourcePath.'#content.xml';
        $xmlContents = file_get_contents('zip://'.$pathToWorkbookFile);

        self::assertNotFalse($xmlContents);
        self::assertStringContainsString("table:name=\"{$expectedName}\"", $xmlContents, $message);
    }
}
