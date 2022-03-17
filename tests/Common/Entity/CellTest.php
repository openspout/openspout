<?php

namespace OpenSpout\Common\Entity;

use DateInterval;
use DateTimeImmutable;
use OpenSpout\Common\Entity\Cell\BooleanCell;
use OpenSpout\Common\Entity\Cell\DateCell;
use OpenSpout\Common\Entity\Cell\EmptyCell;
use OpenSpout\Common\Entity\Cell\ErrorCell;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Common\Entity\Cell\NumericCell;
use OpenSpout\Common\Entity\Cell\StringCell;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CellTest extends TestCase
{
    public function testCellTypeNumeric(): void
    {
        static::assertInstanceOf(NumericCell::class, Cell::fromValue(0));
        static::assertInstanceOf(NumericCell::class, Cell::fromValue(1));
        static::assertInstanceOf(NumericCell::class, Cell::fromValue(10.1));
        static::assertInstanceOf(NumericCell::class, Cell::fromValue(10.10000000000000000000001));
        static::assertInstanceOf(NumericCell::class, Cell::fromValue(0x539));
        static::assertInstanceOf(NumericCell::class, Cell::fromValue(02471));
        static::assertInstanceOf(NumericCell::class, Cell::fromValue(0b10100111001));
        static::assertInstanceOf(NumericCell::class, Cell::fromValue(1337e0));
    }

    public function testCellTypeString(): void
    {
        static::assertInstanceOf(StringCell::class, Cell::fromValue('String!'));
    }

    public function testCellTypeEmptyString(): void
    {
        static::assertInstanceOf(EmptyCell::class, Cell::fromValue(''));
    }

    public function testCellTypeEmptyNull(): void
    {
        static::assertInstanceOf(EmptyCell::class, Cell::fromValue(null));
    }

    public function testCellTypeBool(): void
    {
        static::assertInstanceOf(BooleanCell::class, Cell::fromValue(true));
        static::assertInstanceOf(BooleanCell::class, Cell::fromValue(false));
    }

    public function testCellTypeDate(): void
    {
        static::assertInstanceOf(DateCell::class, Cell::fromValue(new DateTimeImmutable()));
        static::assertInstanceOf(DateCell::class, Cell::fromValue(new DateInterval('P2Y4DT6H8M')));
    }

    public function testCellTypeFormula(): void
    {
        static::assertInstanceOf(FormulaCell::class, Cell::fromValue('=SUM(A1:A2)'));
    }

    public function testCellTypeError(): void
    {
        static::assertInstanceOf(ErrorCell::class, Cell::fromValue([]));
    }

    public function testErroredCellValueShouldBeNull(): void
    {
        $cell = Cell::fromValue([]);
        static::assertInstanceOf(ErrorCell::class, $cell);
        static::assertNull($cell->getValue());
        static::assertSame([], $cell->getRawValue());
    }
}
