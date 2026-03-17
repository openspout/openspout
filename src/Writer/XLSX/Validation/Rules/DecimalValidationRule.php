<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Validation\Rules;

use OpenSpout\Writer\XLSX\Validation\AbstractOperatorValidationRule;
use OpenSpout\Writer\XLSX\Validation\CellReference;
use OpenSpout\Writer\XLSX\Validation\ValidationOperator;
use OpenSpout\Writer\XLSX\Validation\ValidationRuleType;

/**
 * @extends AbstractOperatorValidationRule<float>
 */
final readonly class DecimalValidationRule extends AbstractOperatorValidationRule
{
    public function __construct(
        ValidationOperator $operator,
        CellReference|float $value1,
        CellReference|float|null $value2 = null,
    ) {
        parent::__construct($operator, $value1, $value2);
    }

    public function getValidationRuleType(): ValidationRuleType
    {
        return ValidationRuleType::Decimal;
    }

    /**
     * @param float $value
     */
    protected function serializeValue(mixed $value): string
    {
        return (string) $value;
    }
}
