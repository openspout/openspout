<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Cell;

use OpenSpout\Common\Entity\Cell;

final class BooleanCell extends Cell
{
    public function __construct(public readonly bool $value)
    {
    }

    public function getValue(): bool
    {
        return $this->value;
    }
}
