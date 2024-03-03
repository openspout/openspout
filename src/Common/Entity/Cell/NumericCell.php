<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Cell;

use OpenSpout\Common\Entity\Cell;

final class NumericCell extends Cell
{
    public function __construct(private readonly float|int $value)
    {
    }

    public function getValue(): float|int
    {
        return $this->value;
    }
}
