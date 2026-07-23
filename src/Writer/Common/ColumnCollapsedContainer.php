<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common;

/**
 * @internal
 */
final class ColumnCollapsedContainer
{
    /** @var list<ColumnCollapsed> */
    private array $columnCollapseds = [];

    public function append(ColumnCollapsed $columnCollapsed): void
    {
        $this->columnCollapseds[] = $columnCollapsed;
    }

    /**
     * @return list<ColumnCollapsed>
     */
    public function get(): array
    {
        return $this->columnCollapseds;
    }
}
