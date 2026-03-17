<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Validation\Rules;

use OpenSpout\Writer\XLSX\Validation\DataValidationRuleInterface;
use OpenSpout\Writer\XLSX\Validation\SerializedValidationRule;
use OpenSpout\Writer\XLSX\Validation\ValidationRuleType;

final readonly class CustomValidationRule implements DataValidationRuleInterface
{
    public function __construct(
        public string $formula,
    ) {}

    public function getValidationRuleType(): ValidationRuleType
    {
        return ValidationRuleType::Custom;
    }

    public function serialize(): SerializedValidationRule
    {
        return new SerializedValidationRule(
            type: $this->getValidationRuleType()->value,
            operator: null,
            formula1: htmlspecialchars($this->formula, ENT_XML1),
        );
    }
}
