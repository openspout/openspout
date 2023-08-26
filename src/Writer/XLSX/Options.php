<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\Common\AbstractOptions;
use OpenSpout\Writer\XLSX\Options\EnumPageOrientation;
use OpenSpout\Writer\XLSX\Options\EnumPaperSize;
use OpenSpout\Writer\XLSX\Options\PageMargin;

final class Options extends AbstractOptions
{
    public const DEFAULT_FONT_SIZE = 12;
    public const DEFAULT_FONT_NAME = 'Calibri';

    public bool $SHOULD_USE_INLINE_STRINGS = true;

    /** @var MergeCell[] */
    private array $MERGE_CELLS = [];

    private ?PageMargin $pageMargin = null;

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
     * @param float $top    inches
     * @param float $right  inches
     * @param float $bottom inches
     * @param float $left   inches
     * @param float $header inches
     * @param float $footer inches
     */
    public function setPageMargin(
        float $top = 0.75,
        float $right = 0.7,
        float $bottom = 0.75,
        float $left = 0.7,
        float $header = 0.3,
        float $footer = 0.3
    ): void {
        $this->pageMargin = new PageMargin(
            $top,
            $right,
            $bottom,
            $left,
            $header,
            $footer,
        );
    }

    public function getPageMargin(): PageMargin
    {
        return $this->pageMargin;
    }

    public function hasPageMargin(): bool
    {
        return (bool) $this->pageMargin;
    }

    public function setPageOrientation(EnumPageOrientation $orientation): void
    {
        $this->pageSetup['orientation'] = $orientation->value;
    }

    public function setPaperSize(EnumPaperSize $size): void
    {
        $this->pageSetup['paperSize'] = $size->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPageSetup(): array
    {
        return $this->pageSetup;
    }

    public function hasPageSetup(): bool
    {
        return (bool) $this->pageSetup;
    }
}
