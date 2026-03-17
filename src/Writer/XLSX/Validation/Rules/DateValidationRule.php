<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Validation\Rules;

use DateTimeImmutable;
use OpenSpout\Writer\XLSX\Helper\DateHelper;
use OpenSpout\Writer\XLSX\Validation\AbstractOperatorValidationRule;
use OpenSpout\Writer\XLSX\Validation\CellReference;
use OpenSpout\Writer\XLSX\Validation\ValidationOperator;
use OpenSpout\Writer\XLSX\Validation\ValidationRuleType;

/**
 * @extends AbstractOperatorValidationRule<DateTimeImmutable>
 */
final readonly class DateValidationRule extends AbstractOperatorValidationRule
{
    public function __construct(
        ValidationOperator $operator,
        CellReference|DateTimeImmutable $value1,
        CellReference|DateTimeImmutable|null $value2 = null,
    ) {
        parent::__construct($operator, $value1, $value2);
    }

    public function getValidationRuleType(): ValidationRuleType
    {
        return ValidationRuleType::Date;
    }

    /**
     * @param DateTimeImmutable $value
     */
    protected function serializeValue(mixed $value): string
    {
        return (string) DateHelper::toExcel($value);
    }
}
