<?php

namespace OpenSpout\Common\Entity;

use OpenSpout\Common\Entity\Style\Style;

/**
 * @internal
 */
final class RowTest extends \PHPUnit\Framework\TestCase
{
    public function testValidInstance()
    {
        static::assertInstanceOf(Row::class, new Row([], null));
    }

    public function testSetCells()
    {
        $row = new Row([], null);
        $row->setCells([new Cell(null), new Cell(null)]);

        static::assertSame(2, $row->getNumCells());
    }

    public function testSetCellsResets()
    {
        $row = new Row([], null);
        $row->setCells([new Cell(null), new Cell(null)]);

        static::assertSame(2, $row->getNumCells());

        $row->setCells([new Cell(null)]);

        static::assertSame(1, $row->getNumCells());
    }

    public function testGetCells()
    {
        $row = new Row([], null);

        static::assertSame(0, $row->getNumCells());

        $row->setCells([new Cell(null), new Cell(null)]);

        static::assertSame(2, $row->getNumCells());
    }

    public function testGetCellAtIndex()
    {
        $row = new Row([], null);
        $cellMock = new Cell(null);
        $row->setCellAtIndex($cellMock, 3);

        static::assertSame($cellMock, $row->getCellAtIndex(3));
        static::assertNull($row->getCellAtIndex(10));
    }

    public function testSetCellAtIndex()
    {
        $row = new Row([], null);
        $cellMock = new Cell(null);
        $row->setCellAtIndex($cellMock, 1);

        static::assertSame(2, $row->getNumCells());
        static::assertNull($row->getCellAtIndex(0));
    }

    public function testAddCell()
    {
        $row = new Row([], null);
        $row->setCells([new Cell(null), new Cell(null)]);

        static::assertSame(2, $row->getNumCells());

        $row->addCell(new Cell(null));

        static::assertSame(3, $row->getNumCells());
    }

    public function testFluentInterface()
    {
        $row = new Row([], null);
        $row
            ->addCell(new Cell(null))
            ->setStyle(new Style())
            ->setCells([])
        ;

        static::assertInstanceOf(Row::class, $row);
    }
}
