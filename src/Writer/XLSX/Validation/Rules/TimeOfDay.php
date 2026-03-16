<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Validation\Rules;

use InvalidArgumentException;

final readonly class TimeOfDay
{
    public function __construct(
        public int $hours,
        public int $minutes = 0,
        public int $seconds = 0,
    ) {
        if ($this->hours < 0 || $this->hours > 23) {
            throw new InvalidArgumentException(
                \sprintf('Hours must be between 0 and 23, got %d.', $this->hours)
            );
        }
        if ($this->minutes < 0 || $this->minutes > 59) {
            throw new InvalidArgumentException(
                \sprintf('Minutes must be between 0 and 59, got %d.', $this->minutes)
            );
        }
        if ($this->seconds < 0 || $this->seconds > 59) {
            throw new InvalidArgumentException(
                \sprintf('Seconds must be between 0 and 59, got %d.', $this->seconds)
            );
        }
    }

    public function toDayFraction(): float
    {
        return ($this->hours * 3600 + $this->minutes * 60 + $this->seconds) / 86400;
    }
}
