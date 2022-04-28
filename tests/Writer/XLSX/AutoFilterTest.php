<?php

declare(strict_types=1);

namespace Writer\XLSX;

use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Writer\XLSX\AutoFilter;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class AutoFilterTest extends TestCase
{
    public function testCreateAutofilterSetEmptyRange(): void
    {
        $autoFilter = new AutoFilter();
        self::assertSame('', $autoFilter->getRange(), 'Incorrect AutoFilter range');
    }

    public function testGetRangeShouldReturnThePreviouslySetRange(): void
    {
        $autoFilter = new AutoFilter();
        $customAutofilter = 'A1:AB13';

        $autoFilter->setRange($customAutofilter);
        self::assertSame($customAutofilter, $autoFilter->getRange(), 'Incorrect AutoFilter range');
    }

    public function testSetRangeShouldThrowExceptionIfRangeIsInvalid(): void
    {
        $autoFilter = new AutoFilter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid range format.');
        $autoFilter->setRange('A1B3');
    }

    public function testGetRangeArrayReturnAutofilterRangeAsArray(): void
    {
        $autoFilter = new AutoFilter('A1:AB12');

        $expectedRangeArray = ['A', '1', 'AB', '12'];
        $rangeArray = $autoFilter->getRangeArray();
        self::assertSame($expectedRangeArray, $rangeArray, 'Incorrect AutoFilter range array');
    }

    public function testGetRangeArrayShouldReturnNullIfRangeIsEmpty(): void
    {
        $autoFilter = new AutoFilter();

        $rangeArray = $autoFilter->getRangeArray();
        self::assertNull($rangeArray);
    }
}
