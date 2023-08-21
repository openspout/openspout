<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Cell;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;

final class FormulaCell extends Cell
{
    private string $value;
    private ?string $computedValue;

    public function __construct(string $value, ?Style $style, ?string $computedValue)
    {
        $this->value = $value;
        $this->computedValue = $computedValue;
        parent::__construct($style);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getComputedValue(): ?string
    {
        return $this->computedValue;
    }
}
