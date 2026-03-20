<?php

declare(strict_types=1);

namespace Writer\XLSX\Validation\Rules;

use InvalidArgumentException;
use OpenSpout\Writer\XLSX\Validation\CellReference;
use OpenSpout\Writer\XLSX\Validation\Rules\WholeNumberValidationRule;
use OpenSpout\Writer\XLSX\Validation\ValidationOperator;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WholeNumberValidationRuleTest extends TestCase
{
    public function testIntValuesAreConvertedToFormulas(): void
    {
        $rule = new WholeNumberValidationRule(ValidationOperator::Between, 0, 100);
        $serialized_validation_rule = $rule->serialize();

        self::assertSame('whole', $serialized_validation_rule->type);
        self::assertSame(ValidationOperator::Between->value, $serialized_validation_rule->operator);
        self::assertSame('0', $serialized_validation_rule->formula1);
        self::assertSame('100', $serialized_validation_rule->formula2);
    }

    public function testWholeNumberWithCellReference(): void
    {
        $rule = new WholeNumberValidationRule(
            ValidationOperator::Between,
            new CellReference(0, 1, 0, 1),
            new CellReference(1, 1, 1, 1),
        );
        $serialized_validation_rule = $rule->serialize();

        self::assertSame('whole', $serialized_validation_rule->type);
        self::assertSame('$A$1:$A$1', $serialized_validation_rule->formula1);
        self::assertSame('$B$1:$B$1', $serialized_validation_rule->formula2);
    }

    public function testWholeNumberWithoutSecondValue(): void
    {
        $rule = new WholeNumberValidationRule(ValidationOperator::GreaterThan, 5);
        $serialized_validation_rule = $rule->serialize();

        self::assertSame('whole', $serialized_validation_rule->type);
        self::assertSame('5', $serialized_validation_rule->formula1);
        self::assertNull($serialized_validation_rule->formula2);
    }

    public function testWholeNumberBetweenWithoutSecondValueThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'between' requires a second value.");

        new WholeNumberValidationRule(ValidationOperator::Between, 5);
    }
}
