<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Validation;

interface DataValidationRuleInterface
{
    public function getValidationRuleType(): ValidationRuleType;

    public function serialize(): SerializedValidationRule;
}
