<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common\Manager;

trait ManagesCellSize
{
    /** @var null|float The default column width to use */
    private ?float $defaultColumnWidth;

    /** @var null|float The default row height to use */
    private ?float $defaultRowHeight;

    /** @var array<array-key, array<array-key, float|int>> Array of min-max-width arrays */
    private array $columnWidths = [];

    public function setDefaultColumnWidth(?float $width): void
    {
        $this->defaultColumnWidth = $width;
    }

    public function setDefaultRowHeight(?float $height): void
    {
        $this->defaultRowHeight = $height;
    }

    /**
     * @param int ...$columns One or more columns with this width
     */
    public function setColumnWidth(float $width, int ...$columns): void
    {
        // Gather sequences
        $sequence = [];
        foreach ($columns as $i) {
            $sequenceLength = \count($sequence);
            if ($sequenceLength > 0) {
                $previousValue = $sequence[$sequenceLength - 1];
                if ($i !== $previousValue + 1) {
                    $this->setColumnWidthForRange($width, $sequence[0], $previousValue);
                    $sequence = [];
                }
            }
            $sequence[] = $i;
        }
        $this->setColumnWidthForRange($width, $sequence[0], $sequence[\count($sequence) - 1]);
    }

    /**
     * @param float $width The width to set
     * @param int   $start First column index of the range
     * @param int   $end   Last column index of the range
     */
    public function setColumnWidthForRange(float $width, int $start, int $end): void
    {
        $this->columnWidths[] = [$start, $end, $width];
    }
}
