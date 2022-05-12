<?php

declare(strict_types=1);

namespace OpenSpout\Writer;

final class AutoFilter
{
    /** @var array<int> */
    private array $range = [];

    /**
     * @param 0|positive-int $fromColumnIndex
     * @param positive-int   $fromRow
     * @param 0|positive-int $toColumnIndex
     * @param positive-int   $toRow
     */
    public function __construct(
        int $fromColumnIndex,
        int $fromRow,
        int $toColumnIndex,
        int $toRow
    ) {
        $this->range['fromCol'] = $fromColumnIndex;
        $this->range['fromRow'] = $fromRow;
        $this->range['toCol'] = $toColumnIndex;
        $this->range['toRow'] = $toRow;
    }

    /**
     * Get AutoFilter Cell Range.
     *
     * @return array<int>
     */
    public function getRange(): array
    {
        return $this->range;
    }

    /**
     * @param 0|positive-int $fromColumnIndex
     * @param positive-int   $fromRow
     * @param 0|positive-int $toColumnIndex
     * @param positive-int   $toRow
     */
    public function setRange(
        int $fromColumnIndex,
        int $fromRow,
        int $toColumnIndex,
        int $toRow
    ): void {
        $this->range['fromCol'] = $fromColumnIndex;
        $this->range['fromRow'] = $fromRow;
        $this->range['toCol'] = $toColumnIndex;
        $this->range['toRow'] = $toRow;
    }
}
