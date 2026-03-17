<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Validation;

use InvalidArgumentException;
use OpenSpout\Writer\Common\Helper\CellHelper;

final readonly class CellReference
{
    /**
     * @param 0|positive-int $fromColumnIndex
     * @param positive-int   $fromRow
     * @param 0|positive-int $toColumnIndex
     * @param positive-int   $toRow
     */
    public function __construct(
        public int $fromColumnIndex,
        public int $fromRow,
        public int $toColumnIndex,
        public int $toRow
    ) {
        if ($this->fromRow > $this->toRow || $this->fromColumnIndex > $this->toColumnIndex) {
            throw new InvalidArgumentException('Top-left cell must not be below or to the right of bottom-right cell.');
        }
    }

    public function serialize(): string
    {
        return \sprintf(
            '%s%s:%s%s',
            CellHelper::getColumnLettersFromColumnIndex($this->fromColumnIndex),
            $this->fromRow,
            CellHelper::getColumnLettersFromColumnIndex($this->toColumnIndex),
            $this->toRow,
        );
    }
}
