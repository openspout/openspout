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
        $autoFilter = new AutoFilter(0, 1, 27, 13);
        $customAutofilter = [
            'fromCol' => 0,
            'fromRow' => 1,
            'toCol' => 27,
            'toRow' => 13,
        ];

        self::assertSame($customAutofilter, $autoFilter->getRange(), 'Incorrect AutoFilter range');
    }

    public function testGetRangeShouldReturnThePreviouslySetRange(): void
    {
        $autoFilter = new AutoFilter(0, 1, 2, 2);
        $customAutofilter = [
            'fromCol' => 0,
            'fromRow' => 1,
            'toCol' => 27,
            'toRow' => 13,
        ];

        $autoFilter->setRange(0, 1, 27, 13);
        self::assertSame($customAutofilter, $autoFilter->getRange(), 'Incorrect AutoFilter range');
    }
}
