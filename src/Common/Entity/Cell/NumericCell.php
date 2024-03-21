<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Cell;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;

final class NumericCell extends Cell
{
    public function __construct(private readonly float|int $value, ?Style $style = null)
    {
        parent::__construct($style);
    }

    public function getValue(): float|int
    {
        return $this->value;
    }
}
