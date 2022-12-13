<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity;

use DateInterval;
use DateTimeImmutable;
use OpenSpout\Common\Entity\Cell\BooleanCell;
use OpenSpout\Common\Entity\Cell\DateIntervalCell;
use OpenSpout\Common\Entity\Cell\DateTimeCell;
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
        self::assertInstanceOf(NumericCell::class, Cell::fromValue(0));
        self::assertInstanceOf(NumericCell::class, Cell::fromValue(1));
        self::assertInstanceOf(NumericCell::class, Cell::fromValue(10.2));
        self::assertInstanceOf(NumericCell::class, Cell::fromValue(10.10000000000000000000001));
        self::assertInstanceOf(NumericCell::class, Cell::fromValue(0x539));
        self::assertInstanceOf(NumericCell::class, Cell::fromValue(02471));
        self::assertInstanceOf(NumericCell::class, Cell::fromValue(0b10100111001));
        self::assertInstanceOf(NumericCell::class, Cell::fromValue(1337e0));
    }

    public function testCellTypeString(): void
    {
        self::assertInstanceOf(StringCell::class, Cell::fromValue('String!'));
    }

    public function testCellTypeEmptyString(): void
    {
        self::assertInstanceOf(EmptyCell::class, Cell::fromValue(''));
    }

    public function testCellTypeEmptyNull(): void
    {
        self::assertInstanceOf(EmptyCell::class, Cell::fromValue(null));
    }

    public function testCellTypeBool(): void
    {
        self::assertInstanceOf(BooleanCell::class, Cell::fromValue(true));
        self::assertInstanceOf(BooleanCell::class, Cell::fromValue(false));
    }

    public function testCellTypeDate(): void
    {
        self::assertInstanceOf(DateTimeCell::class, Cell::fromValue(new DateTimeImmutable()));
        self::assertInstanceOf(DateIntervalCell::class, Cell::fromValue(new DateInterval('P2Y4DT6H8M')));
    }

    public function testCellTypeFormula(): void
    {
        self::assertInstanceOf(FormulaCell::class, Cell::fromValue('=SUM(A1:A2)'));
    }

    public function testErroredCellValueShouldBeNull(): void
    {
        $cell = new ErrorCell('#DIV/0', null);
        self::assertNull($cell->getValue());
        self::assertSame('#DIV/0', $cell->getRawValue());
    }
}
