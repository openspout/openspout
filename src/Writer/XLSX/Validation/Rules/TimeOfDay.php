<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Validation\Rules;

final readonly class TimeOfDay
{
    /**
     * @param int<0, 23> $hours
     * @param int<0, 59> $minutes
     * @param int<0, 59> $seconds
     */
    public function __construct(
        public int $hours,
        public int $minutes = 0,
        public int $seconds = 0,
    ) {}

    public function toDayFraction(): float
    {
        return ($this->hours * 3600 + $this->minutes * 60 + $this->seconds) / 86400;
    }
}
