<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\Common\AbstractOptions;

final class Options extends AbstractOptions
{
    public const DEFAULT_FONT_SIZE = 12;
    public const DEFAULT_FONT_NAME = 'Calibri';

    public bool $SHOULD_USE_INLINE_STRINGS = true;

    /** @var array<array-key, array<array-key, int[]>> */
    public array $MERGE_CELLS = [];

    public function __construct()
    {
        parent::__construct();

        $defaultRowStyle = new Style();
        $defaultRowStyle->setFontSize(self::DEFAULT_FONT_SIZE);
        $defaultRowStyle->setFontName(self::DEFAULT_FONT_NAME);

        $this->DEFAULT_ROW_STYLE = $defaultRowStyle;
    }

    /**
     * Merge cells.
     * Row coordinates are indexed from 1, columns from 0 (A = 0),
     * so a merge B2:G2 looks like $writer->mergeCells([1,2], [6, 2]);.
     *
     * You may use CellHelper::getColumnLettersFromColumnIndex() to convert from "B2" to "[1,2]"
     *
     * @param int[] $range1 - top left cell's coordinate [column, row]
     * @param int[] $range2 - bottom right cell's coordinate [column, row]
     */
    public function mergeCells(array $range1, array $range2): void
    {
        $this->MERGE_CELLS[] = [$range1, $range2];
    }
}
