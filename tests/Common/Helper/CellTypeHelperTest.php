<?php

namespace OpenSpout\Common\Helper;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CellTypeHelperTest extends TestCase
{
    public function testIsEmpty(): void
    {
        static::assertTrue(CellTypeHelper::isEmpty(null));
        static::assertTrue(CellTypeHelper::isEmpty(''));

        static::assertFalse(CellTypeHelper::isEmpty('string'));
        static::assertFalse(CellTypeHelper::isEmpty(0));
        static::assertFalse(CellTypeHelper::isEmpty(1));
        static::assertFalse(CellTypeHelper::isEmpty(true));
        static::assertFalse(CellTypeHelper::isEmpty(false));
        static::assertFalse(CellTypeHelper::isEmpty(['string']));
        static::assertFalse(CellTypeHelper::isEmpty(new \stdClass()));
    }

    public function testIsNonEmptyString(): void
    {
        static::assertTrue(CellTypeHelper::isNonEmptyString('string'));

        static::assertFalse(CellTypeHelper::isNonEmptyString(''));
        static::assertFalse(CellTypeHelper::isNonEmptyString(0));
        static::assertFalse(CellTypeHelper::isNonEmptyString(1));
        static::assertFalse(CellTypeHelper::isNonEmptyString(true));
        static::assertFalse(CellTypeHelper::isNonEmptyString(false));
        static::assertFalse(CellTypeHelper::isNonEmptyString(['string']));
        static::assertFalse(CellTypeHelper::isNonEmptyString(new \stdClass()));
        static::assertFalse(CellTypeHelper::isNonEmptyString(null));
    }

    public function testIsNumeric(): void
    {
        static::assertTrue(CellTypeHelper::isNumeric(0));
        static::assertTrue(CellTypeHelper::isNumeric(10));
        static::assertTrue(CellTypeHelper::isNumeric(10.1));
        static::assertTrue(CellTypeHelper::isNumeric(10.10000000000000000000001));
        static::assertTrue(CellTypeHelper::isNumeric(0x539));
        static::assertTrue(CellTypeHelper::isNumeric(02471));
        static::assertTrue(CellTypeHelper::isNumeric(0b10100111001));
        static::assertTrue(CellTypeHelper::isNumeric(1337e0));

        static::assertFalse(CellTypeHelper::isNumeric('0'));
        static::assertFalse(CellTypeHelper::isNumeric('42'));
        static::assertFalse(CellTypeHelper::isNumeric(true));
        static::assertFalse(CellTypeHelper::isNumeric([2]));
        static::assertFalse(CellTypeHelper::isNumeric(new \stdClass()));
        static::assertFalse(CellTypeHelper::isNumeric(null));
    }

    public function testIsBoolean(): void
    {
        static::assertTrue(CellTypeHelper::isBoolean(true));
        static::assertTrue(CellTypeHelper::isBoolean(false));

        static::assertFalse(CellTypeHelper::isBoolean(0));
        static::assertFalse(CellTypeHelper::isBoolean(1));
        static::assertFalse(CellTypeHelper::isBoolean('0'));
        static::assertFalse(CellTypeHelper::isBoolean('1'));
        static::assertFalse(CellTypeHelper::isBoolean('true'));
        static::assertFalse(CellTypeHelper::isBoolean('false'));
        static::assertFalse(CellTypeHelper::isBoolean([true]));
        static::assertFalse(CellTypeHelper::isBoolean(new \stdClass()));
        static::assertFalse(CellTypeHelper::isBoolean(null));
    }

    public function testIsFormula(): void
    {
        static::assertTrue(CellTypeHelper::isFormula('=SUM(A1:A2)'));

        static::assertFalse(CellTypeHelper::isFormula(0));
        static::assertFalse(CellTypeHelper::isFormula(1));
        static::assertFalse(CellTypeHelper::isFormula('0'));
        static::assertFalse(CellTypeHelper::isFormula('1'));
        static::assertFalse(CellTypeHelper::isFormula('true'));
        static::assertFalse(CellTypeHelper::isFormula('false'));
        static::assertFalse(CellTypeHelper::isFormula(''));
        static::assertFalse(CellTypeHelper::isFormula([true]));
        static::assertFalse(CellTypeHelper::isFormula(new \stdClass()));
        static::assertFalse(CellTypeHelper::isFormula(null));
    }
}
