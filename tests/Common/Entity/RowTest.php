<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity;

use InvalidArgumentException;
use OpenSpout\Common\Entity\Style\Style;
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

    public function testRowWithHeight(): void
    {
        $row = new Row([Cell::fromValue('test')], 15.5);
        $newRow = $row->withHeight(20.0);

        self::assertSame(20.0, $newRow->height);
        self::assertSame(15.5, $row->height);
    }

    public function testRowWithCells(): void
    {
        $cells1 = [Cell::fromValue('A'), Cell::fromValue('B')];
        $cells2 = [Cell::fromValue('X'), Cell::fromValue('Y')];
        $row = new Row($cells1, 10.0);
        $newRow = $row->withCells($cells2);

        self::assertSame(['X', 'Y'], $newRow->toArray());
        self::assertSame(['A', 'B'], $row->toArray());
        self::assertSame(10.0, $newRow->height);
    }

    public function testAcceptsAssociativeArraysInNamedConstructors(): void
    {
        $row = Row::fromValues(['foo', 'bar' => 'baz']);
        self::assertIsList($row->cells);

        $style = new Style();
        $row = Row::fromValuesWithStyles(['a' => 1, 'b' => 2], ['b' => $style]);

        self::assertIsList($row->cells);
        self::assertNull($row->cells[0]->style);
        self::assertSame($style, $row->cells[1]->style);

        $row = Row::fromValuesWithStyle(['a' => 1, 'b' => 2], $style);

        self::assertIsList($row->cells);
    }

    public function testDoesNotAcceptUnsortedCellArraysInConstructor(): void
    {
        self::expectException(InvalidArgumentException::class);
        new Row([1 => Cell::fromValue('foo'), 0 => Cell::fromValue('bar')]);
    }
}
