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

    /** @var MergeCell[] */
    private array $MERGE_CELLS = [];

    /** @var array<string, mixed>*/
    private array $pageMargins = [];

    /** @var array<string, mixed> */
    private array $pageSetup = [];

    public function __construct()
    {
        parent::__construct();

        $defaultRowStyle = new Style();
        $defaultRowStyle->setFontSize(self::DEFAULT_FONT_SIZE);
        $defaultRowStyle->setFontName(self::DEFAULT_FONT_NAME);

        $this->DEFAULT_ROW_STYLE = $defaultRowStyle;
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

    /**
     * set Worksheets page margins.
     *
     * @param float $top inches
     * @param float $right inches
     * @param float $bottom inches
     * @param float $left inches
     * @param float $header inches
     * @param float $footer inches
     */
    public function setPageMargins(
        float $top = 0.75,
        float $right = 0.7,
        float $bottom = 0.75,
        float $left = 0.7,
        float $header = 0.3,
        float $footer = 0.3
    ): void {
        $this->pageMargins = [
            'top' => $top,
            'right' => $right,
            'bottom' => $bottom,
            'left' => $left,
            'header' => $header,
            'footer' => $footer,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getPageMargins(): array
    {
        return $this->pageMargins;
    }

    /**
     * @param string $orientation portrait|landscape
     */
    public function setPageOrientation(string $orientation): void
    {
        $this->pageSetup['orientation'] = $orientation;
    }

    public function setPaperSize(string $size): void
    {
        $paperSize = match ($size) {
            'Letter' => 1,
            'Tabloid' => 3,
            'Ledger' => 4,
            'Legal' => 5,
            'Statement' => 6,
            'Executive' => 7,
            'A3' => 8,
            'A4' => 9,
            'A5' => 11,
            'B4' => 12,
            'B5' => 13,
            'Folio' => 14,
            'Quarto' => 15,
            'Standard' => 16,
            'Note' => 18,
            '#9 envelope' => 19,
            '#10 envelope' => 20,
            '#11 envelope' => 21,
            '#12 envelope' => 22,
            '#14 envelope' => 23,
            'C' => 24,
            'D' => 25,
            'E' => 26,
            'DL' => 27,
            'C5' => 28,
            'C3' => 29,
            'C4' => 30,
            'C6' => 31,
            'C65' => 32,
            'B4' => 33,
            'B5' => 34,
            'B6' => 35,
            'Italy envelope' => 36,
            'Monarch envelope' => 37,
            '6 3/4 envelope' => 38,
            'US standard fanfold' => 39,
            'German standard fanfold' => 40,
            'German legal fanfold' => 41,
            default => throw new \UnhandledMatchError("paperSize: {$size} doesn't exists."),
        };

        $this->pageSetup['paperSize'] = $paperSize;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPageSetup(): array
    {
        return $this->pageSetup;
    }
}
