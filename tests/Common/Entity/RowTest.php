<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RowTest extends TestCase
{
    public function testGetCells(): void
    {
        $row = new Row([]);

        self::assertSame(0, $row->getNumCells());

        $row = new Row([Cell::fromValue(null), Cell::fromValue(null)]);

        self::assertSame(2, $row->getNumCells());
    }

    /**
     * @param Cell[] $cells
     */
    #[DataProvider('provideIsEmptyRowCases')]
    public function testIsEmptyRow(array $cells, bool $expectedIsEmpty): void
    {
        self::assertSame($expectedIsEmpty, (new Row($cells))->isEmpty());
    }

    public static function provideIsEmptyRowCases(): iterable
    {
        return [
            // cells, expected isEmpty
            [[], true],
            [[Cell::fromValue('')], true],
            [[Cell::fromValue(''), Cell::fromValue('')], true],
            [[Cell::fromValue(''), Cell::fromValue(''), Cell::fromValue('Okay')], false],
        ];
    }
}
