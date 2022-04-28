<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

use OpenSpout\Common\Exception\InvalidArgumentException;

final class AutoFilter
{
    /** @var string */
    private $range = '';

    public function __construct(string $range = '')
    {
        $this->range = $range;
    }

    /**
     * Get AutoFilter Range.
     */
    public function getRange(): string
    {
        return $this->range;
    }

    /**
     * Set AutoFilter Cell Range.
     */
    public function setRange(string $range): self
    {
        if ('' === $range) {
            $this->range = '';

            return $this;
        }

        $preg_match_all = preg_match_all('/([A-Z]+)(\d+):([A-Z]+)(\d+)/', $range, $matches);
        if (false === $preg_match_all || 0 === $preg_match_all) {
            throw new InvalidArgumentException('Invalid range format.');
        }

        $this->range = $range;

        return $this;
    }

    /**
     * Get AutoFilter Range, as array of [$fromColumnIndex, $fromRow, $toColumnIndex, $toRow] (e.g. [A, 1, F, 8]).
     *
     * @return null|array<string>
     */
    public function getRangeArray(): array|null
    {
        if ('' === $this->range) {
            return null;
        }

        preg_match_all('/([A-Z]+)(\d+):([A-Z]+)(\d+)/', $this->range, $matches);
        $startCol = $matches[1][0];
        $startRow = $matches[2][0];
        $endCol = $matches[3][0];
        $endRow = $matches[4][0];

        return [$startCol, $startRow, $endCol, $endRow];
    }
}
