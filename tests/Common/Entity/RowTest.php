<?php

namespace OpenSpout\Common\Entity;

use OpenSpout\Common\Entity\Style\Style;

/**
 * @internal
 * @coversNothing
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
        $row->setCells([$this->getCellMock(), $this->getCellMock()]);

        static::assertSame(2, $row->getNumCells());
    }

    public function testSetCellsResets()
    {
        $row = new Row([], null);
        $row->setCells([$this->getCellMock(), $this->getCellMock()]);

        static::assertSame(2, $row->getNumCells());

        $row->setCells([$this->getCellMock()]);

        static::assertSame(1, $row->getNumCells());
    }

    public function testGetCells()
    {
        $row = new Row([], null);

        static::assertSame(0, $row->getNumCells());

        $row->setCells([$this->getCellMock(), $this->getCellMock()]);

        static::assertSame(2, $row->getNumCells());
    }

    public function testGetCellAtIndex()
    {
        $row = new Row([], null);
        $cellMock = $this->getCellMock();
        $row->setCellAtIndex($cellMock, 3);

        static::assertSame($cellMock, $row->getCellAtIndex(3));
        static::assertNull($row->getCellAtIndex(10));
    }

    public function testSetCellAtIndex()
    {
        $row = new Row([], null);
        $cellMock = $this->getCellMock();
        $row->setCellAtIndex($cellMock, 1);

        static::assertSame(2, $row->getNumCells());
        static::assertNull($row->getCellAtIndex(0));
    }

    public function testAddCell()
    {
        $row = new Row([], null);
        $row->setCells([$this->getCellMock(), $this->getCellMock()]);

        static::assertSame(2, $row->getNumCells());

        $row->addCell($this->getCellMock());

        static::assertSame(3, $row->getNumCells());
    }

    public function testFluentInterface()
    {
        $row = new Row([], null);
        $row
            ->addCell($this->getCellMock())
            ->setStyle($this->getStyleMock())
            ->setCells([])
        ;

        static::assertInstanceOf(Row::class, $row);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Style
     */
    private function getStyleMock()
    {
        return $this->createMock(Style::class);
    }

    /**
     * @return Cell|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getCellMock()
    {
        return $this->createMock(Cell::class);
    }
}
