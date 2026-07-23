<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common;

/**
 * @internal
 */
final readonly class ColumnOutlineLevel
{
    /**
     * @param positive-int $start
     * @param positive-int $end
     * @param int<0, 7>    $level
     */
    public function __construct(
        public int $start,
        public int $end,
        public int $level,
    ) {}
}
