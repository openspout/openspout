<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common\Entity;

use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\Common\ColumnCollapsed;
use OpenSpout\Writer\Common\ColumnCollapsedContainer;
use OpenSpout\Writer\Common\ColumnHidden;
use OpenSpout\Writer\Common\ColumnHiddenContainer;
use OpenSpout\Writer\Common\ColumnOutlineLevel;
use OpenSpout\Writer\Common\ColumnOutlineLevelContainer;
use OpenSpout\Writer\Common\ColumnWidth;
use OpenSpout\Writer\Common\ColumnWidthContainer;
use OpenSpout\Writer\Common\Manager\SheetManager;
use OpenSpout\Writer\Exception\InvalidSheetNameException;
use OpenSpout\Writer\XLSX\Options\SheetProtection;

/**
 * External representation of a worksheet.
 */
final class Sheet
{
    public const DEFAULT_SHEET_NAME_PREFIX = 'Sheet';

    /** @var 0|positive-int Index of the sheet, based on order in the workbook (zero-based) */
    private readonly int $index;

    /** @var string ID of the sheet's associated workbook. Used to restrict sheet name uniqueness enforcement to a single workbook */
    private readonly string $associatedWorkbookId;

    /** @var string Name of the sheet */
    private string $name;

    /** @var bool Visibility of the sheet */
    private bool $isVisible;

    /** @var SheetManager Sheet manager */
    private readonly SheetManager $sheetManager;

    private ?SheetViewInterface $sheetView = null;

    /** @var 0|positive-int */
    private int $writtenRowCount = 0;

    private ?AutoFilter $autoFilter = null;

    private ColumnWidthContainer $COLUMN_WIDTHS;

    private ColumnHiddenContainer $COLUMN_HIDDEN;

    private ColumnCollapsedContainer $COLUMN_COLLAPSED;

    private ColumnOutlineLevelContainer $COLUMN_OUTLINE_LEVEL;

    /** @var string rows to repeat at top */
    private ?string $printTitleRows = null;

    private ?SheetProtection $sheetProtection = null;

    /**
     * @param 0|positive-int $sheetIndex           Index of the sheet, based on order in the workbook (zero-based)
     * @param string         $associatedWorkbookId ID of the sheet's associated workbook
     * @param SheetManager   $sheetManager         To manage sheets
     */
    public function __construct(int $sheetIndex, string $associatedWorkbookId, SheetManager $sheetManager)
    {
        $this->index = $sheetIndex;
        $this->associatedWorkbookId = $associatedWorkbookId;

        $this->sheetManager = $sheetManager;
        $this->sheetManager->markWorkbookIdAsUsed($associatedWorkbookId);

        $this->setName(self::DEFAULT_SHEET_NAME_PREFIX.($sheetIndex + 1));
        $this->setIsVisible(true);

        $this->COLUMN_WIDTHS = new ColumnWidthContainer();
        $this->COLUMN_HIDDEN = new ColumnHiddenContainer();
        $this->COLUMN_COLLAPSED = new ColumnCollapsedContainer();
        $this->COLUMN_OUTLINE_LEVEL = new ColumnOutlineLevelContainer();
    }

    /**
     * @return 0|positive-int Index of the sheet, based on order in the workbook (zero-based)
     */
    public function getIndex(): int
    {
        return $this->index;
    }

    public function getAssociatedWorkbookId(): string
    {
        return $this->associatedWorkbookId;
    }

    /**
     * @return string Name of the sheet
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the name of the sheet. Note that Excel has some restrictions on the name:
     *  - it should not be blank
     *  - it should not exceed 31 characters
     *  - it should not contain these characters: \ / ? * : [ or ]
     *  - it should be unique.
     *
     * @param string $name Name of the sheet
     *
     * @throws InvalidSheetNameException if the sheet's name is invalid
     */
    public function setName(string $name): self
    {
        $this->sheetManager->throwIfNameIsInvalid($name, $this);

        $this->name = $name;

        $this->sheetManager->markSheetNameAsUsed($this);

        return $this;
    }

    /**
     * @return bool isVisible Visibility of the sheet
     */
    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    /**
     * @param bool $isVisible Visibility of the sheet
     */
    public function setIsVisible(bool $isVisible): self
    {
        $this->isVisible = $isVisible;

        return $this;
    }

    /**
     * @return $this
     */
    public function setSheetView(SheetViewInterface $sheetView): self
    {
        $this->sheetView = $sheetView;

        return $this;
    }

    public function getSheetView(): ?SheetViewInterface
    {
        return $this->sheetView;
    }

    /**
     * @internal
     */
    public function incrementWrittenRowCount(): void
    {
        ++$this->writtenRowCount;
    }

    /**
     * @return 0|positive-int
     */
    public function getWrittenRowCount(): int
    {
        return $this->writtenRowCount;
    }

    /**
     * @return $this
     */
    public function setAutoFilter(?AutoFilter $autoFilter): self
    {
        $this->autoFilter = $autoFilter;

        return $this;
    }

    public function getAutoFilter(): ?AutoFilter
    {
        return $this->autoFilter;
    }

    /**
     * @param positive-int ...$columns One or more columns with this width
     */
    public function setColumnWidth(float $width, int ...$columns): void
    {
        foreach (self::gatherRanges($columns) as [$start, $end]) {
            $this->setColumnWidthForRange($width, $start, $end);
        }
    }

    /**
     * @param float        $width The width to set
     * @param positive-int $start First column index of the range
     * @param positive-int $end   Last column index of the range
     */
    public function setColumnWidthForRange(float $width, int $start, int $end): void
    {
        $this->COLUMN_WIDTHS->append(new ColumnWidth($start, $end, $width));
    }

    /**
     * @internal
     *
     * @return list<ColumnWidth>
     */
    public function getColumnWidths(): array
    {
        return $this->COLUMN_WIDTHS->get();
    }

    /**
     * @param positive-int ...$columns One or more columns to hide (or unhide)
     */
    public function setColumnHidden(bool $hidden, int ...$columns): void
    {
        foreach (self::gatherRanges($columns) as [$start, $end]) {
            $this->setColumnHiddenForRange($hidden, $start, $end);
        }
    }

    /**
     * @param positive-int $start First column index of the range
     * @param positive-int $end   Last column index of the range
     */
    public function setColumnHiddenForRange(bool $hidden, int $start, int $end): void
    {
        $this->COLUMN_HIDDEN->append(new ColumnHidden($start, $end, $hidden));
    }

    /**
     * @internal
     *
     * @return list<ColumnHidden>
     */
    public function getColumnHiddens(): array
    {
        return $this->COLUMN_HIDDEN->get();
    }

    /**
     * @param positive-int ...$columns One or more columns to mark collapsed (or expanded)
     */
    public function setColumnCollapsed(bool $collapsed, int ...$columns): void
    {
        foreach (self::gatherRanges($columns) as [$start, $end]) {
            $this->setColumnCollapsedForRange($collapsed, $start, $end);
        }
    }

    /**
     * @param positive-int $start First column index of the range
     * @param positive-int $end   Last column index of the range
     */
    public function setColumnCollapsedForRange(bool $collapsed, int $start, int $end): void
    {
        $this->COLUMN_COLLAPSED->append(new ColumnCollapsed($start, $end, $collapsed));
    }

    /**
     * @internal
     *
     * @return list<ColumnCollapsed>
     */
    public function getColumnCollapseds(): array
    {
        return $this->COLUMN_COLLAPSED->get();
    }

    /**
     * @param int<0, 7>    $level      Outline (group) level to apply
     * @param positive-int ...$columns One or more columns with this outline level
     */
    public function setColumnOutlineLevel(int $level, int ...$columns): void
    {
        foreach (self::gatherRanges($columns) as [$start, $end]) {
            $this->setColumnOutlineLevelForRange($level, $start, $end);
        }
    }

    /**
     * @param int<0, 7>    $level Outline (group) level to apply
     * @param positive-int $start First column index of the range
     * @param positive-int $end   Last column index of the range
     */
    public function setColumnOutlineLevelForRange(int $level, int $start, int $end): void
    {
        $this->COLUMN_OUTLINE_LEVEL->append(new ColumnOutlineLevel($start, $end, $level));
    }

    /**
     * @internal
     *
     * @return list<ColumnOutlineLevel>
     */
    public function getColumnOutlineLevels(): array
    {
        return $this->COLUMN_OUTLINE_LEVEL->get();
    }

    public function getPrintTitleRows(): ?string
    {
        return $this->printTitleRows;
    }

    public function setPrintTitleRows(string $printTitleRows): void
    {
        $this->printTitleRows = $printTitleRows;
    }

    /**
     * @return $this
     */
    public function setSheetProtection(SheetProtection $sheetProtection): self
    {
        $this->sheetProtection = $sheetProtection;

        return $this;
    }

    public function getSheetProtection(): ?SheetProtection
    {
        return $this->sheetProtection;
    }

    /**
     * Collapses a list of column indexes into contiguous [start, end] ranges.
     *
     * @param array<positive-int> $columns
     *
     * @return list<array{positive-int, positive-int}>
     */
    private static function gatherRanges(array $columns): array
    {
        $ranges = [];
        $sequence = [];
        foreach ($columns as $column) {
            $sequenceLength = \count($sequence);
            if ($sequenceLength > 0) {
                $previousValue = $sequence[$sequenceLength - 1];
                if ($column !== $previousValue + 1) {
                    $ranges[] = [$sequence[0], $previousValue];
                    $sequence = [];
                }
            }
            $sequence[] = $column;
        }
        if ([] !== $sequence) {
            $ranges[] = [$sequence[0], $sequence[\count($sequence) - 1]];
        }

        return $ranges;
    }
}
