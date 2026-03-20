<?php

declare(strict_types=1);

namespace Writer\XLSX\Validation\Rules;

use OpenSpout\Writer\XLSX\Validation\CellReference;
use OpenSpout\Writer\XLSX\Validation\Rules\DecimalValidationRule;
use OpenSpout\Writer\XLSX\Validation\ValidationOperator;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DecimalValidationRuleTest extends TestCase
{
    public function testDecimalWithCellReference(): void
    {
        $rule = new DecimalValidationRule(
            ValidationOperator::Between,
            new CellReference(0, 1, 0, 1),
            new CellReference(0, 2, 0, 2),
        );
        $serialized_validation_rule = $rule->serialize();

        self::assertSame('decimal', $serialized_validation_rule->type);
        self::assertSame('between', $serialized_validation_rule->operator);
        self::assertSame('$A$1:$A$1', $serialized_validation_rule->formula1);
        self::assertSame('$A$2:$A$2', $serialized_validation_rule->formula2);
    }

    public function testDecimalsAreConvertedToStrings(): void
    {
        $rule = new DecimalValidationRule(ValidationOperator::Between, 1.1, 2.5);
        $serialized_validation_rule = $rule->serialize();

        self::assertSame('1.1', $serialized_validation_rule->formula1);
        self::assertSame('2.5', $serialized_validation_rule->formula2);
    }

    public function testNegativeValuesAreSupported(): void
    {
        $rule = new DecimalValidationRule(ValidationOperator::Between, -9.9, -0.1);
        $serialized_validation_rule = $rule->serialize();

        self::assertSame('-9.9', $serialized_validation_rule->formula1);
        self::assertSame('-0.1', $serialized_validation_rule->formula2);
    }

    public function testZeroIsSupported(): void
    {
        $rule = new DecimalValidationRule(ValidationOperator::GreaterThanOrEqual, 0.0);
        $serialized_validation_rule = $rule->serialize();

        self::assertSame('0', $serialized_validation_rule->formula1);
    }

    public function testTrailingZerosAreRemoved(): void
    {
        $rule = new DecimalValidationRule(ValidationOperator::GreaterThanOrEqual, 2.500);
        $serialized_validation_rule = $rule->serialize();

        self::assertSame('2.5', $serialized_validation_rule->formula1);
    }

    public function testFloatingPointArithmeticIsRoundedToExcelPrecision(): void
    {
        $rule = new DecimalValidationRule(ValidationOperator::GreaterThan, 0.1 + 0.2);
        $serialized_validation_rule = $rule->serialize();

        self::assertSame('0.3', $serialized_validation_rule->formula1);
    }
}
