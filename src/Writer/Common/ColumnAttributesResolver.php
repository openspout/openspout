<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common;

use OpenSpout\Writer\Common\Entity\Sheet;

/**
 * Combines the independent per-attribute declarations of a sheet (widths, hidden,
 * collapsed and outline-level) into a minimal list of column ranges, each sharing
 * an identical set of effective attributes. Overlapping declarations resolve with
 * "last declared wins", matching ColumnWidthContainer.
 *
 * @internal
 */
final readonly class ColumnAttributesResolver
{
    /**
     * @return list<ResolvedColumn>
     */
    public function resolve(Sheet $sheet): array
    {
        $widths = $sheet->getColumnWidths();
        $hiddens = $sheet->getColumnHiddens();
        $collapseds = $sheet->getColumnCollapseds();
        $levels = $sheet->getColumnOutlineLevels();

        /** @var array<positive-int, true> $touched */
        $touched = [];
        $minCol = null;
        $maxCol = 0;
        foreach ([$widths, $hiddens, $collapseds, $levels] as $entries) {
            foreach ($entries as $entry) {
                for ($column = $entry->start; $column <= $entry->end; ++$column) {
                    $touched[$column] = true;
                }
                $minCol = null === $minCol ? $entry->start : min($minCol, $entry->start);
                $maxCol = max($maxCol, $entry->end);
            }
        }

        if (null === $minCol) {
            return [];
        }

        $resolved = [];

        /** @var null|array{start: positive-int, end: positive-int, width: ?float, hidden: ?bool, collapsed: ?bool, level: null|int<0, 7>} $pending */
        $pending = null;

        for ($column = $minCol; $column <= $maxCol; ++$column) {
            if (!isset($touched[$column])) {
                // Gap between declared columns breaks the current run
                if (null !== $pending) {
                    $resolved[] = new ResolvedColumn($pending['start'], $pending['end'], $pending['width'], $pending['hidden'], $pending['collapsed'], $pending['level']);
                    $pending = null;
                }

                continue;
            }

            $width = self::effectiveWidth($widths, $column);
            $hidden = self::effectiveHidden($hiddens, $column);
            $collapsed = self::effectiveCollapsed($collapseds, $column);
            $level = self::effectiveOutlineLevel($levels, $column);

            if (
                null !== $pending
                && $pending['end'] + 1 === $column
                && $width === $pending['width']
                && $hidden === $pending['hidden']
                && $collapsed === $pending['collapsed']
                && $level === $pending['level']
            ) {
                $pending['end'] = $column;

                continue;
            }

            if (null !== $pending) {
                $resolved[] = new ResolvedColumn($pending['start'], $pending['end'], $pending['width'], $pending['hidden'], $pending['collapsed'], $pending['level']);
            }

            $pending = [
                'start' => $column,
                'end' => $column,
                'width' => $width,
                'hidden' => $hidden,
                'collapsed' => $collapsed,
                'level' => $level,
            ];
        }

        if (null !== $pending) {
            $resolved[] = new ResolvedColumn($pending['start'], $pending['end'], $pending['width'], $pending['hidden'], $pending['collapsed'], $pending['level']);
        }

        return $resolved;
    }

    /**
     * @param list<ColumnWidth> $entries
     * @param positive-int      $column
     */
    private static function effectiveWidth(array $entries, int $column): ?float
    {
        for ($index = \count($entries) - 1; $index >= 0; --$index) {
            $entry = $entries[$index];
            if ($entry->start <= $column && $entry->end >= $column) {
                return $entry->width;
            }
        }

        return null;
    }

    /**
     * @param list<ColumnHidden> $entries
     * @param positive-int       $column
     */
    private static function effectiveHidden(array $entries, int $column): ?bool
    {
        for ($index = \count($entries) - 1; $index >= 0; --$index) {
            $entry = $entries[$index];
            if ($entry->start <= $column && $entry->end >= $column) {
                return $entry->hidden;
            }
        }

        return null;
    }

    /**
     * @param list<ColumnCollapsed> $entries
     * @param positive-int          $column
     */
    private static function effectiveCollapsed(array $entries, int $column): ?bool
    {
        for ($index = \count($entries) - 1; $index >= 0; --$index) {
            $entry = $entries[$index];
            if ($entry->start <= $column && $entry->end >= $column) {
                return $entry->collapsed;
            }
        }

        return null;
    }

    /**
     * @param list<ColumnOutlineLevel> $entries
     * @param positive-int             $column
     *
     * @return null|int<0, 7>
     */
    private static function effectiveOutlineLevel(array $entries, int $column): ?int
    {
        for ($index = \count($entries) - 1; $index >= 0; --$index) {
            $entry = $entries[$index];
            if ($entry->start <= $column && $entry->end >= $column) {
                return $entry->level;
            }
        }

        return null;
    }
}
