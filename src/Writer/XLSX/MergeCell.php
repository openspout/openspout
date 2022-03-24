<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

/**
 * @internal
 */
final class MergeCell
{
    public function __construct(
        public int $topLeftColumn,
        public int $topLeftRow,
        public int $bottomRightColumn,
        public int $bottomRightRow
    ) {
    }
}
