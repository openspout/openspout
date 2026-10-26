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

    public function testRowWithOutlineLevel(): void
    {
        $row = new Row([Cell::fromValue('test')], 15.5, 1);
        $newRow = $row->withOutlineLevel(2);

        self::assertSame(2, $newRow->outlineLevel);
        self::assertSame(1, $row->outlineLevel);
        self::assertSame(15.5, $newRow->height);
    }

    public function testRowWithHiddenAndCollapsed(): void
    {
        $row = new Row([Cell::fromValue('test')]);
        $newRow = $row->withHidden(true)->withCollapsed(true);

        self::assertTrue($newRow->hidden);
        self::assertTrue($newRow->collapsed);
        self::assertFalse($row->hidden);
        self::assertFalse($row->collapsed);
    }

    public function testWithersPreserveOtherAttributes(): void
    {
        $cells = [Cell::fromValue('test')];
        $row = new Row($cells, 15.5, 3, hidden: true);

        $otherCells = [Cell::fromValue('other')];
        self::assertEquals(new Row($otherCells, 15.5, 3, hidden: true), $row->withCells($otherCells));
        self::assertEquals(new Row($cells, 20.0, 3, hidden: true), $row->withHeight(20.0));
        self::assertEquals(new Row($cells, 15.5, 1, hidden: true), $row->withOutlineLevel(1));
        self::assertEquals(new Row($cells, 15.5, 3), $row->withHidden(false));
        self::assertEquals(new Row($cells, 15.5, 3, hidden: true, collapsed: true), $row->withCollapsed(true));
    }

    public function testNamedConstructorsAcceptRowAttributes(): void
    {
        $style = new Style();

        self::assertEquals(new Row([Cell::fromValue('a')]), Row::fromValues(['a']));
        self::assertEquals(new Row([Cell::fromValue('a', $style)]), Row::fromValuesWithStyles(['a'], [$style]));
        self::assertEquals(new Row([Cell::fromValue('a', $style)]), Row::fromValuesWithStyle(['a'], $style));
        self::assertEquals(
            new Row([Cell::fromValue('a')], 15.5, 1, hidden: true, collapsed: true),
            Row::fromValues(['a'], 15.5, 1, hidden: true, collapsed: true)
        );
        self::assertEquals(
            new Row([Cell::fromValue('a', $style)], 15.5, 2, hidden: true),
            Row::fromValuesWithStyles(['a'], [$style], 15.5, 2, hidden: true)
        );
        self::assertEquals(
            new Row([Cell::fromValue('a', $style)], 15.5, 3, collapsed: true),
            Row::fromValuesWithStyle(['a'], $style, 15.5, 3, collapsed: true)
        );
    }

    /**
     * @param int<0, 7> $outlineLevel
     */
    #[DataProvider('provideAcceptsOutlineLevelWithinRangeCases')]
    public function testAcceptsOutlineLevelWithinRange(int $outlineLevel): void
    {
        self::assertSame($outlineLevel, (new Row([], outlineLevel: $outlineLevel))->outlineLevel);
    }

    public static function provideAcceptsOutlineLevelWithinRangeCases(): iterable
    {
        return [
            [0],
            [Row::MAX_OUTLINE_LEVEL],
        ];
    }

    #[DataProvider('provideDoesNotAcceptOutOfRangeOutlineLevelCases')]
    public function testDoesNotAcceptOutOfRangeOutlineLevel(int $outlineLevel): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Row([], outlineLevel: $outlineLevel);
    }

    public static function provideDoesNotAcceptOutOfRangeOutlineLevelCases(): iterable
    {
        return [
            [-1],
            [Row::MAX_OUTLINE_LEVEL + 1],
        ];
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
