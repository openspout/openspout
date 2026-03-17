<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Validation\Rules;

use InvalidArgumentException;
use OpenSpout\Writer\XLSX\Validation\CellReference;
use OpenSpout\Writer\XLSX\Validation\DataValidationRuleInterface;
use OpenSpout\Writer\XLSX\Validation\ValidationRuleType;

final readonly class ListValidationRule implements DataValidationRuleInterface
{
    /**
     * @param CellReference|non-empty-list<non-empty-string> $value
     */
    public function __construct(
        public array|CellReference $value,
    ) {
        if ($this->value instanceof CellReference) {
            return;
        }
		if ([] === $this->value) {
			throw new InvalidArgumentException('ListValidationRule requires at least one option.');
		}
		foreach ($this->value as $option) {
			if (! str_contains($option, ',')) {
			    continue;
			}
			throw new InvalidArgumentException(
				\sprintf(
					"List validation option '%s' contains a comma, which is not allowed as it is used as a delimiter.",
					$option,
				)
			);
		}
    }

    public function getValidationRuleType(): ValidationRuleType
    {
        return ValidationRuleType::List;
    }
}
