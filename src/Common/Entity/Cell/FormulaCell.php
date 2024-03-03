<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Cell;

use DateInterval;
use DateTimeImmutable;
use OpenSpout\Common\Entity\Cell;

final class FormulaCell extends Cell
{
    public function __construct(
        private readonly string $value,
        private readonly null|DateInterval|DateTimeImmutable|float|int|string $computedValue = null,
    ) {
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getComputedValue(): null|DateInterval|DateTimeImmutable|float|int|string
    {
        return $this->computedValue;
    }
}
