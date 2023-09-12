<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Cell;

use DateTimeImmutable;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;

final class FormulaCell extends Cell
{
    public function __construct(
        private readonly string $value,
        ?Style $style,
        private readonly DateTimeImmutable|float|int|string|null $computedValue = null,
    ) {
        parent::__construct($style);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getComputedValue(): DateTimeImmutable|float|int|string|null
    {
        return $this->computedValue;
    }
}
