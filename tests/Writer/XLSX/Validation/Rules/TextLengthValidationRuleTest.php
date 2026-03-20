<?php

declare(strict_types=1);

namespace Writer\XLSX\Validation\Rules;

use OpenSpout\Writer\XLSX\Validation\CellReference;
use OpenSpout\Writer\XLSX\Validation\Rules\TextLengthValidationRule;
use OpenSpout\Writer\XLSX\Validation\ValidationOperator;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class TextLengthValidationRuleTest extends TestCase
{
    public function testIntValuesAreConvertedToFormulas(): void
    {
        $rule = new TextLengthValidationRule(ValidationOperator::Between, 5, 50);
        $serialized_validation_rule = $rule->serialize();

        self::assertSame('textLength', $serialized_validation_rule->type);
        self::assertSame(ValidationOperator::Between->value, $serialized_validation_rule->operator);
        self::assertSame('5', $serialized_validation_rule->formula1);
        self::assertSame('50', $serialized_validation_rule->formula2);
    }

    public function testTextLengthWithCellReference(): void
    {
        $rule = new TextLengthValidationRule(
            ValidationOperator::LessThanOrEqual,
            new CellReference(0, 1, 0, 1),
        );
        $serialized_validation_rule = $rule->serialize();

        self::assertSame('textLength', $serialized_validation_rule->type);
        self::assertSame('$A$1:$A$1', $serialized_validation_rule->formula1);
        self::assertNull($serialized_validation_rule->formula2);
    }
}
