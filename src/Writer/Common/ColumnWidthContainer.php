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

    public function resolveIntervals(): void
    {
        $segments = $this->buildSegments();
        $this->columnWidths = $this->mergeAdjacentSegments($segments);
    }

    /**
     * @return list<array{start: positive-int, end: positive-int, width: float}>
     */
    private function buildSegments(): array
    {
        $points = [];

        foreach ($this->columnWidths as $columnWidth) {
            $points[] = $columnWidth->start;
            $points[] = $columnWidth->end + 1;
        }

        sort($points);

        $segments = [];
        $pointCount = \count($points);
        for ($pointIndex = 0; $pointIndex < $pointCount - 1; ++$pointIndex) {
            $segStart = $points[$pointIndex];

            /** @var positive-int $segEnd */
            $segEnd = $points[$pointIndex + 1] - 1;
            // Last declared interval wins
            for ($columnWidthIndex = \count($this->columnWidths) - 1; $columnWidthIndex >= 0; --$columnWidthIndex) {
                $columnWidth = $this->columnWidths[$columnWidthIndex];
                if ($columnWidth->start <= $segStart && $columnWidth->end >= $segEnd) {
                    $segments[] = ['start' => $segStart, 'end' => $segEnd, 'width' => $columnWidth->width];

                    break;
                }
            }
        }

        return $segments;
    }

    /**
     * @param list<array{start: positive-int, end: positive-int, width: float}> $segments
     *
     * @return list<ColumnWidth>
     */
    private function mergeAdjacentSegments(array $segments): array
    {
        $mergedSegments = [];
        foreach ($segments as $segment) {
            if ([] === $mergedSegments) {
                $mergedSegments[] = $segment;

                continue;
            }

            $lastIndex = array_key_last($mergedSegments);
            $last = &$mergedSegments[$lastIndex];

            $isContinuous = $last['end'] + 1 === $segment['start'];
            $sameWidth = $last['width'] === $segment['width'];

            if ($isContinuous && $sameWidth) {
                $last['end'] = $segment['end'];
            } else {
                $mergedSegments[] = $segment;
            }
        }

        $columnWidths = [];
        foreach ($mergedSegments as $mergedSegment) {
            $columnWidths[] = new ColumnWidth($mergedSegment['start'], $mergedSegment['end'], $mergedSegment['width']);
        }

        return $columnWidths;
    }
}
