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
        private ValidationOperator $operator,
        private mixed $value1,
        private mixed $value2
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

    public function getOperator(): ValidationOperator
    {
        return $this->operator;
    }

    /**
     * @return CellReference|T
     */
    public function getValue1(): mixed
    {
        return $this->value1;
    }

    /**
     * @return null|CellReference|T
     */
    public function getValue2(): mixed
    {
        return $this->value2;
    }
}
