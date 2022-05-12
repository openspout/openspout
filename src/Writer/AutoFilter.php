<?php

declare(strict_types=1);

namespace OpenSpout\Writer;

final class AutoFilter
{
    /** @var 0|positive-int */
    public $fromColumnIndex;

    /** @var positive-int */
    public $fromRow;

    /** @var 0|positive-int */
    public $toColumnIndex;

    /** @var positive-int */
    public $toRow;

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
        $this->fromColumnIndex = $fromColumnIndex;
        $this->fromRow = $fromRow;
        $this->toColumnIndex = $toColumnIndex;
        $this->toRow = $toRow;
    }
}
