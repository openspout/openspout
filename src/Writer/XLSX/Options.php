<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\Common\AbstractOptions;
use OpenSpout\Writer\Exception\WriterNotSet;
use WeakReference;

final class Options extends AbstractOptions
{
    public const DEFAULT_FONT_SIZE = 12;
    public const DEFAULT_FONT_NAME = 'Calibri';

    public bool $SHOULD_USE_INLINE_STRINGS = true;

    /**
     * @var null|WeakReference<Writer>
     */
    private WeakReference|null $writer;

    /** @var MergeCell[] */
    private array $MERGE_CELLS = [];

    public function __construct(Writer|null $writer = null)
    {
        parent::__construct();

        $defaultRowStyle = new Style();
        $defaultRowStyle->setFontSize(self::DEFAULT_FONT_SIZE);
        $defaultRowStyle->setFontName(self::DEFAULT_FONT_NAME);

        $this->DEFAULT_ROW_STYLE = $defaultRowStyle;

        $this->setWriter($writer);
    }

    public function getWriter(): Writer|null
    {
        return $this->writer?->get();
    }

    public function setWriter(Writer|null $writer): void
    {
        $this->writer = null === $writer ? null : WeakReference::create($writer);
    }

    /**
     * Row coordinates are indexed from 1, columns from 0 (A = 0),
     * so a merge B2:G2 looks like $writer->mergeCells(1, 2, 6, 2);.
     *
     * @param 0|positive-int $topLeftColumn
     * @param positive-int   $topLeftRow
     * @param 0|positive-int $bottomRightColumn
     * @param positive-int   $bottomRightRow
     */
    public function mergeCells(
        int $topLeftColumn,
        int $topLeftRow,
        int $bottomRightColumn,
        int $bottomRightRow
    ): void {
        $writer = $this->getWriter();
        if (null === $writer) {
            throw new WriterNotSet('Unable to merge cells. You should set writer first.');
        }

        $this->MERGE_CELLS[] = new MergeCell(
            $writer->getCurrentSheet()->getIndex(),
            $topLeftColumn,
            $topLeftRow,
            $bottomRightColumn,
            $bottomRightRow
        );
    }

    /**
     * @internal
     *
     * @return MergeCell[]
     */
    public function getMergeCells(): array
    {
        return $this->MERGE_CELLS;
    }
}
