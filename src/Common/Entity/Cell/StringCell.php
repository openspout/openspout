<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Cell;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;

final class StringCell extends Cell
{
    public function __construct(private readonly string $value, ?Style $style = null)
    {
        parent::__construct($style);
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
