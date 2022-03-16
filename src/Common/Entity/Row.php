<?php

namespace OpenSpout\Common\Entity;

use OpenSpout\Common\Entity\Style\Style;

final class Row
{
    /**
     * The cells in this row.
     *
     * @var Cell[]
     */
    private array $cells = [];

    /**
     * The row style.
     */
    private Style $style;

    /**
     * Row height (default is 15).
     */
    private string $height = '15';

    /**
     * Row constructor.
     *
     * @param Cell[] $cells
     */
    public function __construct(array $cells, ?Style $style)
    {
        $this
            ->setCells($cells)
            ->setStyle($style)
        ;
    }

    /**
     * @return Cell[] $cells
     */
    public function getCells(): array
    {
        return $this->cells;
    }

    /**
     * @param Cell[] $cells
     */
    public function setCells(array $cells): self
    {
        $this->cells = [];
        foreach ($cells as $cell) {
            $this->addCell($cell);
        }

        return $this;
    }

    public function setCellAtIndex(Cell $cell, int $cellIndex): self
    {
        $this->cells[$cellIndex] = $cell;

        return $this;
    }

    public function getCellAtIndex(int $cellIndex): ?Cell
    {
        return $this->cells[$cellIndex] ?? null;
    }

    public function addCell(Cell $cell): self
    {
        $this->cells[] = $cell;

        return $this;
    }

    public function getNumCells(): int
    {
        // When using "setCellAtIndex", it's possible to
        // have "$this->cells" contain holes.
        if (empty($this->cells)) {
            return 0;
        }

        return max(array_keys($this->cells)) + 1;
    }

    public function getStyle(): Style
    {
        return $this->style;
    }

    public function setStyle(?Style $style): self
    {
        $this->style = $style ?: new Style();

        return $this;
    }

    /**
     * @return mixed[] The row values, as array
     */
    public function toArray(): array
    {
        return array_map(static function (Cell $cell): mixed {
            return $cell->getValue();
        }, $this->cells);
    }

    /**
     * Set row height.
     */
    public function setHeight(string $height): self
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Returns row height.
     */
    public function getHeight(): string
    {
        return $this->height;
    }
}
