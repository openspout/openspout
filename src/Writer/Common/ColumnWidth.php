<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common;

/**
 * @internal
 */
final class ColumnWidth
{
    public function __construct(
        public int $start,
        public int $end,
        public float $width,
    ) {
    }
}
