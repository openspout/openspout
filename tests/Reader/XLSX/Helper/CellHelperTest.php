<?php

namespace OpenSpout\Reader\XLSX\Helper;

use OpenSpout\Common\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CellHelperTest extends TestCase
{
    public function dataProviderForTestGetColumnIndexFromCellIndex(): array
    {
        return [
            ['A1', 0],
            ['Z3', 25],
            ['AA5', 26],
            ['AB24', 27],
            ['BC5', 54],
            ['BCZ99', 1455],
        ];
    }

    /**
     * @dataProvider dataProviderForTestGetColumnIndexFromCellIndex
     */
    public function testGetColumnIndexFromCellIndex(string $cellIndex, int $expectedColumnIndex): void
    {
        static::assertSame($expectedColumnIndex, CellHelper::getColumnIndexFromCellIndex($cellIndex));
    }

    public function testGetColumnIndexFromCellIndexShouldThrowIfInvalidCellIndex(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CellHelper::getColumnIndexFromCellIndex('InvalidCellIndex');
    }
}
