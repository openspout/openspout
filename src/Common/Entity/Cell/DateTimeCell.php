<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Cell;

use DateTimeInterface;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;

final class DateTimeCell extends Cell
{
    public function __construct(private readonly DateTimeInterface $value, ?Style $style = null)
    {
        parent::__construct($style);
    }

    public function getValue(): DateTimeInterface
    {
        return $this->value;
    }
}
