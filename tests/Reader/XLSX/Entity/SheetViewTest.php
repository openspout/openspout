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
        $sheetView = new SheetView();

        $this->expectException(InvalidArgumentException::class);

        $sheetView->setFreezeRow(-1);
    }

    public function testFreezingFirstCellShouldntGeneratePaneTag(): void
    {
        $sheetView = new SheetView();
        $sheetView->setFreezeRow(3);
        $sheetView->setFreezeColumn('B');

        self::assertStringContainsString('<pane', $sheetView->getXml());

        $sheetView->setFreezeRow(1);
        $sheetView->setFreezeColumn('A');

        self::assertStringNotContainsString('<pane', $sheetView->getXml());
    }
}
