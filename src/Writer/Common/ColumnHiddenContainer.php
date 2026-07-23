<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common;

/**
 * @internal
 */
final class ColumnHiddenContainer
{
    /** @var list<ColumnHidden> */
    private array $columnHiddens = [];

    public function append(ColumnHidden $columnHidden): void
    {
        $this->columnHiddens[] = $columnHidden;
    }

    /**
     * @return list<ColumnHidden>
     */
    public function get(): array
    {
        return $this->columnHiddens;
    }
}
