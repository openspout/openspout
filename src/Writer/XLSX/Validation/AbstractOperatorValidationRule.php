<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Validation;

use DateTimeImmutable;
use InvalidArgumentException;
use OpenSpout\Writer\XLSX\Validation\Rules\TimeOfDay;

/**
 * @template T of int|float|DateTimeImmutable|TimeOfDay
 */
abstract readonly class AbstractOperatorValidationRule implements DataValidationRuleInterface
{
    /**
     * @param CellReference|T      $value1
     * @param null|CellReference|T $value2
     */
    public function __construct(
        public ValidationOperator $operator,
        public mixed $value1,
        public mixed $value2
    ) {
        if (
            \in_array($operator, [ValidationOperator::Between, ValidationOperator::NotBetween], true)
            && null === $this->value2
        ) {
            throw new InvalidArgumentException(
                \sprintf("Operator '%s' requires a second value.", $operator->value)
            );
        }
    }

    public function serialize(): SerializedValidationRule
    {
        return new SerializedValidationRule(
            type: $this->getValidationRuleType()->value,
            operator: $this->operator->value,
            formula1: $this->transformValue($this->value1),
            formula2: $this->transformValue($this->value2)
        );
    }

    /**
     * @param T $value
     */
    abstract protected function serializeValue(mixed $value): string;

    /**
     * @param null|CellReference|T $value
     */
    private function transformValue(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof CellReference) {
            return $value->serialize();
        }

        return $this->serializeValue($value);
    }
}
