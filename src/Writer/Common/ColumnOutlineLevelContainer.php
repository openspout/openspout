<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common;

/**
 * @internal
 */
final class ColumnOutlineLevelContainer
{
    /** @var list<ColumnOutlineLevel> */
    private array $columnOutlineLevels = [];

    public function append(ColumnOutlineLevel $columnOutlineLevel): void
    {
        $this->columnOutlineLevels[] = $columnOutlineLevel;
    }

    /**
     * @return list<ColumnOutlineLevel>
     */
    public function get(): array
    {
        return $this->columnOutlineLevels;
    }
}
