<?php

namespace OpenSpout\Common\Entity;

use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Helper\CellTypeHelper;

class Cell
{
    /**
     * Numeric cell type (whole numbers, fractional numbers, dates).
     */
    public const TYPE_NUMERIC = 0;

    /**
     * String (text) cell type.
     */
    public const TYPE_STRING = 1;

    /**
     * Formula cell type
     * Not used at the moment.
     */
    public const TYPE_FORMULA = 2;

    /**
     * Empty cell type.
     */
    public const TYPE_EMPTY = 3;

    /**
     * Boolean cell type.
     */
    public const TYPE_BOOLEAN = 4;

    /**
     * Date cell type.
     */
    public const TYPE_DATE = 5;

    /**
     * Error cell type.
     */
    public const TYPE_ERROR = 6;

    /**
     * The value of this cell.
     *
     * @var null|mixed
     */
    protected $value;

    /**
     * The cell type.
     */
    protected ?int $type;

    /**
     * The cell style.
     */
    protected Style $style;

    /**
     * @param null|mixed $value
     */
    public function __construct($value, Style $style = null)
    {
        $this->setValue($value);
        $this->setStyle($style);
    }

    public function __toString(): string
    {
        return (string) $this->getValue();
    }

    /**
     * @param null|mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
        $this->type = $this->detectType($value);
    }

    /**
     * @return null|mixed
     */
    public function getValue(): mixed
    {
        return !$this->isError() ? $this->value : null;
    }

    public function getValueEvenIfError(): mixed
    {
        return $this->value;
    }

    public function setStyle(?Style $style)
    {
        $this->style = $style ?: new Style();
    }

    public function getStyle(): Style
    {
        return $this->style;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type)
    {
        $this->type = $type;
    }

    public function isBoolean(): bool
    {
        return self::TYPE_BOOLEAN === $this->type;
    }

    public function isEmpty(): bool
    {
        return self::TYPE_EMPTY === $this->type;
    }

    public function isNumeric(): bool
    {
        return self::TYPE_NUMERIC === $this->type;
    }

    public function isString(): bool
    {
        return self::TYPE_STRING === $this->type;
    }

    public function isDate(): bool
    {
        return self::TYPE_DATE === $this->type;
    }

    public function isFormula(): bool
    {
        return self::TYPE_FORMULA === $this->type;
    }

    public function isError(): bool
    {
        return self::TYPE_ERROR === $this->type;
    }

    /**
     * Get the current value type.
     *
     * @param null|mixed $value
     */
    protected function detectType($value): int
    {
        if (CellTypeHelper::isBoolean($value)) {
            return self::TYPE_BOOLEAN;
        }
        if (CellTypeHelper::isEmpty($value)) {
            return self::TYPE_EMPTY;
        }
        if (CellTypeHelper::isNumeric($value)) {
            return self::TYPE_NUMERIC;
        }
        if (CellTypeHelper::isDateTimeOrDateInterval($value)) {
            return self::TYPE_DATE;
        }
        if (CellTypeHelper::isFormula($value)) {
            return self::TYPE_FORMULA;
        }
        if (CellTypeHelper::isNonEmptyString($value)) {
            return self::TYPE_STRING;
        }

        return self::TYPE_ERROR;
    }
}
