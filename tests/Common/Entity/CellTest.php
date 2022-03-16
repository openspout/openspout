<?php

namespace OpenSpout\Common\Entity;

use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CellTest extends TestCase
{
    public function testValidInstance(): void
    {
        static::assertInstanceOf(Cell::class, new Cell('cell'));
    }

    public function testCellTypeNumeric(): void
    {
        static::assertTrue((new Cell(0))->isNumeric());
        static::assertTrue((new Cell(1))->isNumeric());
    }

    public function testCellTypeString(): void
    {
        static::assertTrue((new Cell('String!'))->isString());
    }

    public function testCellTypeEmptyString(): void
    {
        static::assertTrue((new Cell(''))->isEmpty());
    }

    public function testCellTypeEmptyNull(): void
    {
        static::assertTrue((new Cell(null))->isEmpty());
    }

    public function testCellTypeBool(): void
    {
        static::assertTrue((new Cell(true))->isBoolean());
        static::assertTrue((new Cell(false))->isBoolean());
    }

    public function testCellTypeDate(): void
    {
        static::assertTrue((new Cell(new DateTimeImmutable()))->isDate());
        static::assertTrue((new Cell(new DateInterval('P2Y4DT6H8M')))->isDate());
    }

    public function testCellTypeFormula(): void
    {
        static::assertTrue((new Cell('=SUM(A1:A2)'))->isFormula());
    }

    public function testCellTypeError(): void
    {
        static::assertTrue((new Cell([]))->isError());
    }

    public function testErroredCellValueShouldBeNull(): void
    {
        $cell = new Cell([]);
        static::assertTrue($cell->isError());
        static::assertNull($cell->getValue());
    }
}
