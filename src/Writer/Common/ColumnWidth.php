<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common;

/**
 * @internal
 */
final class ColumnWidth
{
    private ColumnAttributes $columnAttributes;

    /**
     * @param positive-int $start
     * @param positive-int $end
     */
    public function __construct(
        public readonly int $start,
        public readonly int $end,
        public readonly float $width,
    ) {
        $this->columnAttributes = new ColumnAttributes();
    }

    public function setColumnAttributes(ColumnAttributes $columnAttributes) : ColumnAttributes {
        $this->columnAttributes = $columnAttributes;

        return $this->columnAttributes;
    }

    public function getColumnAttributes() : ColumnAttributes {
        return $this->columnAttributes;
    }
}
