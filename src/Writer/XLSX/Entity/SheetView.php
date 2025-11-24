<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Entity;

use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Reader\XLSX\Helper\CellHelper;

final readonly class SheetView
{
    /**
     * @param non-empty-string $view
     * @param non-empty-string $topLeftCell
     * @param non-negative-int $colorId
     * @param non-negative-int $zoomScale
     * @param non-negative-int $zoomScaleNormal
     * @param non-negative-int $zoomScalePageLayoutView
     * @param non-negative-int $workbookViewId
     * @param non-negative-int $freezeRow
     * @param non-empty-string $freezeColumn
     */
    public function __construct(
        public bool $showFormulas = false,
        public bool $showGridLines = true,
        public bool $showRowColHeaders = true,
        public bool $showZeros = true,
        public bool $rightToLeft = false,
        public bool $tabSelected = false,
        public bool $showOutlineSymbols = true,
        public bool $defaultGridColor = true,
        public string $view = 'normal',
        public string $topLeftCell = 'A1',
        public int $colorId = 64,
        public int $zoomScale = 100,
        public int $zoomScaleNormal = 100,
        public int $zoomScalePageLayoutView = 100,
        public int $workbookViewId = 0,
        public int $freezeRow = 0,
        public string $freezeColumn = 'A',
    ) {
        if ($this->freezeRow < 0) {
            throw new InvalidArgumentException('Freeze row must be a positive integer');
        }
        if ($this->freezeColumn !== strtoupper($this->freezeColumn)) {
            throw new InvalidArgumentException('Freeze column must be provided uppercase');
        }
    }

    public function withShowFormulas(bool $showFormulas): self
    {
        $values = get_object_vars($this);
        $values['showFormulas'] = $showFormulas;

        return new self(...$values);
    }

    public function withShowGridLines(bool $showGridLines): self
    {
        $values = get_object_vars($this);
        $values['showGridLines'] = $showGridLines;

        return new self(...$values);
    }

    public function withShowRowColHeaders(bool $showRowColHeaders): self
    {
        $values = get_object_vars($this);
        $values['showRowColHeaders'] = $showRowColHeaders;

        return new self(...$values);
    }

    public function withShowZeros(bool $showZeros): self
    {
        $values = get_object_vars($this);
        $values['showZeros'] = $showZeros;

        return new self(...$values);
    }

    public function withRightToLeft(bool $rightToLeft): self
    {
        $values = get_object_vars($this);
        $values['rightToLeft'] = $rightToLeft;

        return new self(...$values);
    }

    public function withTabSelected(bool $tabSelected): self
    {
        $values = get_object_vars($this);
        $values['tabSelected'] = $tabSelected;

        return new self(...$values);
    }

    public function withShowOutlineSymbols(bool $showOutlineSymbols): self
    {
        $values = get_object_vars($this);
        $values['showOutlineSymbols'] = $showOutlineSymbols;

        return new self(...$values);
    }

    public function withDefaultGridColor(bool $defaultGridColor): self
    {
        $values = get_object_vars($this);
        $values['defaultGridColor'] = $defaultGridColor;

        return new self(...$values);
    }

    public function withView(string $view): self
    {
        $values = get_object_vars($this);
        $values['view'] = $view;

        return new self(...$values);
    }

    public function withTopLeftCell(string $topLeftCell): self
    {
        $values = get_object_vars($this);
        $values['topLeftCell'] = $topLeftCell;

        return new self(...$values);
    }

    public function withColorId(int $colorId): self
    {
        $values = get_object_vars($this);
        $values['colorId'] = $colorId;

        return new self(...$values);
    }

    public function withZoomScale(int $zoomScale): self
    {
        $values = get_object_vars($this);
        $values['zoomScale'] = $zoomScale;

        return new self(...$values);
    }

    public function withZoomScaleNormal(int $zoomScaleNormal): self
    {
        $values = get_object_vars($this);
        $values['zoomScaleNormal'] = $zoomScaleNormal;

        return new self(...$values);
    }

    public function withZoomScalePageLayoutView(int $zoomScalePageLayoutView): self
    {
        $values = get_object_vars($this);
        $values['zoomScalePageLayoutView'] = $zoomScalePageLayoutView;

        return new self(...$values);
    }

    public function withWorkbookViewId(int $workbookViewId): self
    {
        $values = get_object_vars($this);
        $values['workbookViewId'] = $workbookViewId;

        return new self(...$values);
    }

    /**
     * @param positive-int $freezeRow Set to 2 to fix the first row
     */
    public function withFreezeRow(int $freezeRow): self
    {
        $values = get_object_vars($this);
        $values['freezeRow'] = $freezeRow;

        return new self(...$values);
    }

    /**
     * @param string $freezeColumn Set to B to fix the first column
     */
    public function withFreezeColumn(string $freezeColumn): self
    {
        $values = get_object_vars($this);
        $values['freezeColumn'] = $freezeColumn;

        return new self(...$values);
    }

    public function getXml(): string
    {
        return '<sheetView'.$this->getSheetViewAttributes().'>'
        .$this->getFreezeCellPaneXml()
        .'</sheetView>';
    }

    private function getSheetViewAttributes(): string
    {
        return $this->generateAttributes([
            'showFormulas' => $this->showFormulas,
            'showGridLines' => $this->showGridLines,
            'showRowColHeaders' => $this->showRowColHeaders,
            'showZeros' => $this->showZeros,
            'rightToLeft' => $this->rightToLeft,
            'tabSelected' => $this->tabSelected,
            'showOutlineSymbols' => $this->showOutlineSymbols,
            'defaultGridColor' => $this->defaultGridColor,
            'view' => $this->view,
            'topLeftCell' => $this->topLeftCell,
            'colorId' => $this->colorId,
            'zoomScale' => $this->zoomScale,
            'zoomScaleNormal' => $this->zoomScaleNormal,
            'zoomScalePageLayoutView' => $this->zoomScalePageLayoutView,
            'workbookViewId' => $this->workbookViewId,
        ]);
    }

    private function getFreezeCellPaneXml(): string
    {
        if ($this->freezeRow < 2 && 'A' === $this->freezeColumn) {
            return '';
        }

        $columnIndex = CellHelper::getColumnIndexFromCellIndex($this->freezeColumn.'1');

        return '<pane'.$this->generateAttributes([
            'xSplit' => $columnIndex,
            'ySplit' => $this->freezeRow - 1,
            'topLeftCell' => $this->freezeColumn.$this->freezeRow,
            'activePane' => 'bottomRight',
            'state' => 'frozen',
        ]).'/>';
    }

    /**
     * @param array<string, bool|int|string> $data with key containing the attribute name and value containing the attribute value
     */
    private function generateAttributes(array $data): string
    {
        // Create attribute for each key
        $attributes = array_map(static function (string $key, bool|int|string $value): string {
            if (\is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            return $key.'="'.$value.'"';
        }, array_keys($data), $data);

        // Append all attributes
        return ' '.implode(' ', $attributes);
    }
}
