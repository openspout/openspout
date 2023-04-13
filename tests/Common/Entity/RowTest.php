<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity;

use DateInterval;
use DateTime;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 */
final class RowTest extends \PHPUnit\Framework\TestCase
{
    public function testSetCells(): void
    {
        $row = new Row([], null);
        $row->setCells([Cell::fromValue(null), Cell::fromValue(null)]);

        self::assertSame(2, $row->getNumCells());
    }

    public function testSetCellsResets(): void
    {
        $row = new Row([], null);
        $row->setCells([Cell::fromValue(null), Cell::fromValue(null)]);

        self::assertSame(2, $row->getNumCells());

        $row->setCells([Cell::fromValue(null)]);

        self::assertSame(1, $row->getNumCells());
    }

    public function testGetCells(): void
    {
        $row = new Row([], null);

        self::assertSame(0, $row->getNumCells());

        $row->setCells([Cell::fromValue(null), Cell::fromValue(null)]);

        self::assertSame(2, $row->getNumCells());
    }

    public function testGetCellAtIndex(): void
    {
        $row = new Row([], null);
        $cellMock = Cell::fromValue(null);
        $row->setCellAtIndex($cellMock, 3);

        self::assertSame($cellMock, $row->getCellAtIndex(3));
        self::assertNull($row->getCellAtIndex(10));
    }

    public function testSetCellAtIndex(): void
    {
        $row = new Row([], null);
        $cellMock = Cell::fromValue(null);
        $row->setCellAtIndex($cellMock, 1);

        self::assertSame(2, $row->getNumCells());
        self::assertNull($row->getCellAtIndex(0));
    }

    public function testAddCell(): void
    {
        $row = new Row([], null);
        $row->setCells([Cell::fromValue(null), Cell::fromValue(null)]);

        self::assertSame(2, $row->getNumCells());

        $row->addCell(Cell::fromValue(null));

        self::assertSame(3, $row->getNumCells());
    }

    public function testRowFromArrayValuesSameAsRowToArrayValues(): void
    {
        $supportedValueTypes = [
            null,
            true,
            new DateTime('2022-12-12 12:22:22.0'),
            new DateInterval('P2D'),
            5.55,
            10,
            'string',
        ];

        self::assertSame($supportedValueTypes, Row::fromValues($supportedValueTypes)->toArray());
    }

    /**
     * @param Cell[] $cells
     */
    #[DataProvider('dataProviderForTestIsEmptyRow')]
    public function testIsEmptyRow(array $cells, bool $expectedIsEmpty): void
    {
        $row = new Row($cells, null);

        self::assertSame($expectedIsEmpty, $row->isEmpty());
    }

    public static function dataProviderForTestIsEmptyRow(): array
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
