<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Validation\Rules;

use OpenSpout\Writer\XLSX\Validation\CellReference;
use OpenSpout\Writer\XLSX\Validation\ValidationOperator;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class TimeValidationRuleTest extends TestCase
{
    public function testTimeIsConvertedToDayFraction(): void
    {
        $rule = new TimeValidationRule(
            ValidationOperator::Between,
            new TimeOfDay(6, 0, 0),   // 0.25
            new TimeOfDay(12, 0, 0),  // 0.5
        );
        $serialized_validation_rule = $rule->serialize();

        self::assertSame('time', $serialized_validation_rule->type);
        self::assertSame('between', $serialized_validation_rule->operator);
        self::assertSame('0.25', $serialized_validation_rule->formula1);
        self::assertSame('0.5', $serialized_validation_rule->formula2);
    }

    public function testTimeWithMinutesAndSeconds(): void
    {
        $rule = new TimeValidationRule(
            ValidationOperator::GreaterThan,
            new TimeOfDay(9, 30, 5),
        );
        $serialized_validation_rule = $rule->serialize();

        self::assertSame('0.3958912037037', $serialized_validation_rule->formula1);
    }

    public function testMidnightIsZero(): void
    {
        $rule = new TimeValidationRule(
            ValidationOperator::GreaterThanOrEqual,
            new TimeOfDay(0, 0, 0),
        );
        $serialized_validation_rule = $rule->serialize();

        self::assertSame('0', $serialized_validation_rule->formula1);
    }

    public function testDateWithCellReference(): void
    {
        $rule = new DateValidationRule(
            ValidationOperator::GreaterThan,
            new CellReference(0, 1, 0, 1),
        );
        $serialized_validation_rule = $rule->serialize();

        self::assertSame('date', $serialized_validation_rule->type);
        self::assertSame('$A$1:$A$1', $serialized_validation_rule->formula1);
    }
}
