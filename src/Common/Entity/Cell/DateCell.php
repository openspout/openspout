<?php

namespace OpenSpout\Common\Entity\Cell;

use DateInterval;
use DateTimeInterface;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Style\Style;

final class DateCell extends Cell
{
    private DateTimeInterface|DateInterval $value;

    public function __construct(DateTimeInterface|DateInterval $value, ?Style $style)
    {
        $this->value = $value;
        parent::__construct($style);
    }

    public function getValue(): DateTimeInterface|DateInterval
    {
        return $this->value;
    }
}
