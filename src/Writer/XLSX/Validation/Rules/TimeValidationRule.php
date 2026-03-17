<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Validation\Rules;

use OpenSpout\Writer\XLSX\Validation\AbstractOperatorValidationRule;
use OpenSpout\Writer\XLSX\Validation\CellReference;
use OpenSpout\Writer\XLSX\Validation\ValidationOperator;
use OpenSpout\Writer\XLSX\Validation\ValidationRuleType;

/**
 * @extends AbstractOperatorValidationRule<TimeOfDay>
 */
final readonly class TimeValidationRule extends AbstractOperatorValidationRule
{
    public function __construct(
        ValidationOperator $operator,
        CellReference|TimeOfDay $value1,
        CellReference|TimeOfDay|null $value2 = null,
    ) {
        parent::__construct($operator, $value1, $value2);
    }

    public function getValidationRuleType(): ValidationRuleType
    {
        return ValidationRuleType::Time;
    }

    /**
     * @param TimeOfDay $value
     */
    protected function serializeValue(mixed $value): string
    {
        return (string) $value->toDayFraction();
    }
}
