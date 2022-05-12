<?php

declare(strict_types=1);

namespace Writer\XLSX;

use OpenSpout\Writer\AutoFilter;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class AutoFilterTest extends TestCase
{
    public function testCreateAutofilterSetCorrectRange(): void
    {
        $fromCol = 0;
        $fromRow = 1;
        $toCol = 27;
        $toRow = 13;
        $autoFilter = new AutoFilter($fromCol, $fromRow, $toCol, $toRow);

        self::assertSame($fromCol, $autoFilter->fromColumnIndex, 'Incorrect AutoFilter range');
        self::assertSame($fromRow, $autoFilter->fromRow, 'Incorrect AutoFilter range');
        self::assertSame($toCol, $autoFilter->toColumnIndex, 'Incorrect AutoFilter range');
        self::assertSame($toRow, $autoFilter->toRow, 'Incorrect AutoFilter range');
    }
}
