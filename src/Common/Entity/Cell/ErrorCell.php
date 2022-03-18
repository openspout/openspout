<?php

namespace OpenSpout\Common\Entity\Cell;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;

final class ErrorCell extends Cell
{
    private mixed $value;

    public function __construct(mixed $value, ?Style $style)
    {
        $this->value = $value;
        parent::__construct($style);
    }

    public function getValue(): mixed
    {
        return null;
    }

    public function getRawValue(): mixed
    {
        return $this->value;
    }
}
