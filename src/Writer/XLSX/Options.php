<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\Common\AbstractOptions;
use WeakMap;

final class Options extends AbstractOptions
{
    public const DEFAULT_FONT_SIZE = 12;
    public const DEFAULT_FONT_NAME = 'Calibri';

    public bool $SHOULD_USE_INLINE_STRINGS = true;

    /** @var MergeCell[] */
    private array $MERGE_CELLS = [];

    /** @var WeakMap<Row, RowAttributes> */
    private WeakMap $rowsAttributes;
    private ?int $outlineMaxLevel = null;

    public function __construct()
    {
        parent::__construct();

        $defaultRowStyle = new Style();
        $defaultRowStyle->setFontSize(self::DEFAULT_FONT_SIZE);
        $defaultRowStyle->setFontName(self::DEFAULT_FONT_NAME);

        $this->DEFAULT_ROW_STYLE = $defaultRowStyle;
        $this->rowsAttributes = new WeakMap();
    }

    /**
     * Row coordinates are indexed from 1, columns from 0 (A = 0),
     * so a merge B2:G2 looks like $writer->mergeCells(1, 2, 6, 2);.
     *
     * @param 0|positive-int $topLeftColumn
     * @param positive-int   $topLeftRow
     * @param 0|positive-int $bottomRightColumn
     * @param positive-int   $bottomRightRow
     * @param 0|positive-int $sheetIndex
     */
    public function mergeCells(
        int $topLeftColumn,
        int $topLeftRow,
        int $bottomRightColumn,
        int $bottomRightRow,
        int $sheetIndex = 0,
    ): void {
        $this->MERGE_CELLS[] = new MergeCell(
            $sheetIndex,
            $topLeftColumn,
            $topLeftRow,
            $bottomRightColumn,
            $bottomRightRow
        );
    }

    /**
     * @return MergeCell[]
     *
     * @internal
     */
    public function getMergeCells(): array
    {
        return $this->MERGE_CELLS;
    }

    public function setRowAttributes(Row $row, RowAttributes $attributes): Row
    {
        $this->rowsAttributes[$row] = $attributes;
        $this->outlineMaxLevel = max($attributes->getOutlineLevel(), (int) $this->outlineMaxLevel);

        return $row;
    }

    public function getRowAttributes(Row $row): RowAttributes
    {
        if (!isset($this->rowsAttributes[$row])) {
            $this->rowsAttributes[$row] = new RowAttributes();
        }

        return $this->rowsAttributes[$row];
    }

    public function getOutlineMaxLevel(): ?int
    {
        return $this->outlineMaxLevel;
    }
}
