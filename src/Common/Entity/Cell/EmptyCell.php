<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Cell;

use OpenSpout\Common\Entity\Cell;

final class EmptyCell extends Cell
{
    public function __construct(private readonly ?string $value)
    {
    }

    public function getValue(): ?string
    {
        return $this->value;
    }
}
