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

        self::assertStringContainsString('<pane', $sheetView->getXml());

        $sheetView = new SheetView(
            freezeRow: 1,
            freezeColumn: 'A',
        );

        self::assertStringNotContainsString('<pane', $sheetView->getXml());
    }
}
