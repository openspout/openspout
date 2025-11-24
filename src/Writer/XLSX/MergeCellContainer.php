<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

/**
 * @internal
 */
final class MergeCellContainer
{
    /** @var list<MergeCell> */
    private array $mergeCells = [];

    public function append(MergeCell $mergeCell): void
    {
        $this->mergeCells[] = $mergeCell;
    }

    /**
     * @return list<MergeCell>
     */
    public function get(): array
    {
        return $this->mergeCells;
    }
}
