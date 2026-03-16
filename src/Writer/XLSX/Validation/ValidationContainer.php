<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Validation;

final class ValidationContainer
{
    /** @var list<ValidationRule> */
    private array $validationRules = [];

    public function append(ValidationRule $validationRule): void
    {
        $this->validationRules[] = $validationRule;
    }

    /**
     * @return list<ValidationRule>
     */
    public function get(): array
    {
        return $this->validationRules;
    }
}
