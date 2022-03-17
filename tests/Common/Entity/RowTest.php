<?php

namespace OpenSpout\Common\Entity;

/**
 * @internal
 */
final class RowTest extends \PHPUnit\Framework\TestCase
{
    public function testSetCells(): void
    {
        $row = new Row([], null);
        $row->setCells([Cell::fromValue(null), Cell::fromValue(null)]);

        static::assertSame(2, $row->getNumCells());
    }

    public function testSetCellsResets(): void
    {
        $row = new Row([], null);
        $row->setCells([Cell::fromValue(null), Cell::fromValue(null)]);

        static::assertSame(2, $row->getNumCells());

        $row->setCells([Cell::fromValue(null)]);

        static::assertSame(1, $row->getNumCells());
    }

    public function testGetCells(): void
    {
        $row = new Row([], null);

        static::assertSame(0, $row->getNumCells());

        $row->setCells([Cell::fromValue(null), Cell::fromValue(null)]);

        static::assertSame(2, $row->getNumCells());
    }

    public function testGetCellAtIndex(): void
    {
        $row = new Row([], null);
        $cellMock = Cell::fromValue(null);
        $row->setCellAtIndex($cellMock, 3);

        static::assertSame($cellMock, $row->getCellAtIndex(3));
        static::assertNull($row->getCellAtIndex(10));
    }

    public function testSetCellAtIndex(): void
    {
        $row = new Row([], null);
        $cellMock = Cell::fromValue(null);
        $row->setCellAtIndex($cellMock, 1);

        static::assertSame(2, $row->getNumCells());
        static::assertNull($row->getCellAtIndex(0));
    }

    public function testAddCell(): void
    {
        $row = new Row([], null);
        $row->setCells([Cell::fromValue(null), Cell::fromValue(null)]);

        static::assertSame(2, $row->getNumCells());

        $row->addCell(Cell::fromValue(null));

        static::assertSame(3, $row->getNumCells());
    }
}
