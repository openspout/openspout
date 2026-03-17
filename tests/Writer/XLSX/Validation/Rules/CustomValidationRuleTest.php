<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Validation\Rules;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CustomValidationRuleTest extends TestCase
{
    public function testGetFormula1EscapesXmlSpecialCharacters(): void
    {
        $rule = new CustomValidationRule('A1>0&A1<100');
        $serialized_validation_rule = $rule->serialize();

        self::assertEquals('A1&gt;0&amp;A1&lt;100', $serialized_validation_rule->formula1);
    }

    public function testGetFormula1EscapesQuotesInFormulas(): void
    {
        $rule = new CustomValidationRule('A1="Special \"Value\""');
        $serialized_validation_rule = $rule->serialize();

        self::assertEquals('A1="Special \"Value\""', $serialized_validation_rule->formula1);
    }

    public function testGetFormula1ReturnsSimpleFormulaUnchanged(): void
    {
        $formula = 'SUM(A1:B10)+15';
        $rule = new CustomValidationRule($formula);
        $serialized_validation_rule = $rule->serialize();

        self::assertEquals($formula, $serialized_validation_rule->formula1);
    }
}
