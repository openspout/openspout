<?php

declare(strict_types=1);

namespace Reader\XLSX\Entity;

use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SheetViewTest extends TestCase
{
    public function testFreezeRowMustBePositiveInt(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SheetView(freezeRow: -1);
    }

    public function testFreezingFirstCellShouldntGeneratePaneTag(): void
    {
        $sheetView = new SheetView(
            freezeRow: 3,
            freezeColumn: 'B',
        );

        $xml = $sheetView->getXml();
        self::assertStringContainsString('<pane', $xml);
        self::assertStringContainsString('activePane="bottomRight"', $xml);

        $sheetView = new SheetView(
            freezeRow: 1,
            freezeColumn: 'A',
        );

        self::assertStringNotContainsString('<pane', $sheetView->getXml());
    }

    public function testFreezingOnlyAColumnShouldGenerateValidPaneMetadata(): void
    {
        $sheetView = (new SheetView())->withFreezeColumn('B');

        $xml = $sheetView->getXml();

        self::assertStringContainsString('<pane', $xml);
        self::assertStringContainsString('xSplit="1"', $xml);
        self::assertStringNotContainsString('ySplit', $xml);
        self::assertStringContainsString('topLeftCell="B1"', $xml);
        self::assertStringContainsString('activePane="topRight"', $xml);
    }

    public function testFreezingOnlyARowShouldGenerateValidPaneMetadata(): void
    {
        $sheetView = (new SheetView())->withFreezeRow(2);

        $xml = $sheetView->getXml();

        self::assertStringContainsString('<pane', $xml);
        self::assertStringNotContainsString('xSplit', $xml);
        self::assertStringContainsString('ySplit="1"', $xml);
        self::assertStringContainsString('topLeftCell="A2"', $xml);
        self::assertStringContainsString('activePane="bottomLeft"', $xml);
    }
}
