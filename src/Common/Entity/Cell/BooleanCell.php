<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Cell;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;

final class BooleanCell extends Cell
{
    public function __construct(public readonly bool $value, ?Style $style = null)
    {
        parent::__construct($style);
    }

    public function getValue(): bool
    {
        return $this->value;
    }
}
