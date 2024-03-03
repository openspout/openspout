<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Cell;

use DateTimeInterface;
use OpenSpout\Common\Entity\Cell;

final class DateTimeCell extends Cell
{
    public function __construct(private readonly DateTimeInterface $value)
    {
    }

    public function getValue(): DateTimeInterface
    {
        return $this->value;
    }
}
