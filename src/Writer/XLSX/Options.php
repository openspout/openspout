<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\Common\AbstractOptions;
use OpenSpout\Writer\XLSX\Options\HeaderFooter;
use OpenSpout\Writer\XLSX\Options\PageMargin;
use OpenSpout\Writer\XLSX\Options\PageSetup;
use OpenSpout\Writer\XLSX\Options\WorkbookProtection;
use OpenSpout\Writer\XLSX\Validation\DataValidationRuleInterface;
use OpenSpout\Writer\XLSX\Validation\ValidationContainer;
use OpenSpout\Writer\XLSX\Validation\ValidationDisplay;
use OpenSpout\Writer\XLSX\Validation\ValidationRule;

final readonly class Options extends AbstractOptions
{
    public const int DEFAULT_FONT_SIZE = 12;
    public const string DEFAULT_FONT_NAME = 'Calibri';

    private MergeCellContainer $MERGE_CELLS;
    private ValidationContainer $VALIDATION_CELLS;

    public function __construct(
        Style $FALLBACK_STYLE = new Style(
            fontSize: self::DEFAULT_FONT_SIZE,
            fontName: self::DEFAULT_FONT_NAME,
        ),
        bool $SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY = true,
        ?float $DEFAULT_COLUMN_WIDTH = null,
        ?float $DEFAULT_ROW_HEIGHT = null,
        ?string $tempFolder = null,
        public bool $SHOULD_USE_INLINE_STRINGS = true,
        public ?PageMargin $pageMargin = null,
        public ?PageSetup $pageSetup = null,
        public ?HeaderFooter $headerFooter = null,
        public ?WorkbookProtection $workbookProtection = null,
        public Properties $properties = new Properties(),
    ) {
        parent::__construct(
            $FALLBACK_STYLE,
            $SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY,
            $DEFAULT_COLUMN_WIDTH,
            $DEFAULT_ROW_HEIGHT,
            $tempFolder,
        );

        $this->MERGE_CELLS = new MergeCellContainer();
        $this->VALIDATION_CELLS = new ValidationContainer();
    }

    /**
     * Row coordinates are indexed from 1, columns from 0 (A = 0),
     * so a merge B2:G2 looks like $writer->mergeCells(1, 2, 6, 2);.
     *
     * @param non-negative-int $topLeftColumn
     * @param positive-int     $topLeftRow
     * @param non-negative-int $bottomRightColumn
     * @param positive-int     $bottomRightRow
     * @param non-negative-int $sheetIndex
     */
    public function mergeCells(
        int $topLeftColumn,
        int $topLeftRow,
        int $bottomRightColumn,
        int $bottomRightRow,
        int $sheetIndex = 0,
    ): void {
        $this->MERGE_CELLS->append(new MergeCell(
            $sheetIndex,
            $topLeftColumn,
            $topLeftRow,
            $bottomRightColumn,
            $bottomRightRow
        ));
    }

    /**
     * @return list<MergeCell>
     *
     * @internal
     */
    public function getMergeCells(): array
    {
        return $this->MERGE_CELLS->get();
    }

    /**
     * Row coordinates are indexed from 1, columns from 0 (A = 0),
     * so a validation on B2:G10 looks like $options->addValidation(1, 2, 6, 10, new ListValidationRule([...]));.
     *
     * @param non-negative-int $topLeftColumn
     * @param positive-int     $topLeftRow
     * @param non-negative-int $bottomRightColumn
     * @param positive-int     $bottomRightRow
     * @param non-negative-int $sheetIndex
     */
    public function addValidation(
        int $topLeftColumn,
        int $topLeftRow,
        int $bottomRightColumn,
        int $bottomRightRow,
        DataValidationRuleInterface $rule,
        ValidationDisplay $validation_display = new ValidationDisplay(),
        int $sheetIndex = 0,
    ): void {
        $this->VALIDATION_CELLS->append(new ValidationRule(
            $sheetIndex,
            $topLeftColumn,
            $topLeftRow,
            $bottomRightColumn,
            $bottomRightRow,
            $rule,
            $validation_display,
        ));
    }

    /**
     * @return list<ValidationRule>
     *
     * @internal
     */
    public function getValidationRules(): array
    {
        return $this->VALIDATION_CELLS->get();
    }
}
