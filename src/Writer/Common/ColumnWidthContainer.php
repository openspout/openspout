<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common;

/**
 * @internal
 */
final class ColumnWidthContainer
{
    /** @var list<ColumnWidth> */
    private array $columnWidths = [];

    public function append(ColumnWidth $columnWidth): void
    {
        $this->columnWidths[] = $columnWidth;
    }

    /**
     * @return list<ColumnWidth>
     */
    public function get(): array
    {
        return $this->columnWidths;
    }
}
