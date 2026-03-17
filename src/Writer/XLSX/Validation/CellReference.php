<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Validation;

use InvalidArgumentException;

final readonly class CellReference
{
    /**
     * @param non-negative-int $topLeftColumn
     * @param non-negative-int $topLeftRow
     * @param non-negative-int $bottomRightColumn
     * @param non-negative-int $bottomRightRow
     */
    public function __construct(
        public int $topLeftColumn,
        public int $topLeftRow,
        public int $bottomRightColumn,
        public int $bottomRightRow,
    ) {
        if ($this->topLeftRow > $this->bottomRightRow || $this->topLeftColumn > $this->bottomRightColumn) {
            throw new InvalidArgumentException('Top-left cell must not be below or to the right of bottom-right cell.');
        }
    }
}
