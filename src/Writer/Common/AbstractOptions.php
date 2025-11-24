<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common;

use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\TempFolderCheck;

abstract readonly class AbstractOptions
{
    /** @var non-empty-string */
    public string $tempFolder;

    private ColumnWidthContainer $COLUMN_WIDTHS;

    public function __construct(
        public Style $FALLBACK_STYLE = new Style(),
        public bool $SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY = true,
        public ?float $DEFAULT_COLUMN_WIDTH = null,
        public ?float $DEFAULT_ROW_HEIGHT = null,
        ?string $tempFolder = null,
    ) {
        $tempFolder ??= sys_get_temp_dir();
        \assert('' !== $tempFolder);
        $this->tempFolder = $tempFolder;
        (new TempFolderCheck())->assertTempFolder($this->tempFolder);

        $this->COLUMN_WIDTHS = new ColumnWidthContainer();
    }

    /**
     * Columns are indexed from 1 (A = 1).
     *
     * @param positive-int ...$columns One or more columns with this width
     */
    final public function setColumnWidth(float $width, int ...$columns): void
    {
        // Gather sequences
        $sequence = [];
        foreach ($columns as $column) {
            $sequenceLength = \count($sequence);
            if ($sequenceLength > 0) {
                $previousValue = $sequence[$sequenceLength - 1];
                if ($column !== $previousValue + 1) {
                    $this->setColumnWidthForRange($width, $sequence[0], $previousValue);
                    $sequence = [];
                }
            }
            $sequence[] = $column;
        }
        $this->setColumnWidthForRange($width, $sequence[0], $sequence[\count($sequence) - 1]);
    }

    /**
     * Columns are indexed from 1 (A = 1).
     *
     * @param float        $width The width to set
     * @param positive-int $start First column index of the range
     * @param positive-int $end   Last column index of the range
     */
    final public function setColumnWidthForRange(float $width, int $start, int $end): void
    {
        $this->COLUMN_WIDTHS->append(new ColumnWidth($start, $end, $width));
    }

    /**
     * @internal
     *
     * @return list<ColumnWidth>
     */
    final public function getColumnWidths(): array
    {
        return $this->COLUMN_WIDTHS->get();
    }
}
