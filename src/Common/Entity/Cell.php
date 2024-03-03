<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity;

use DateInterval;
use DateTimeInterface;
use OpenSpout\Common\Entity\Cell\BooleanCell;
use OpenSpout\Common\Entity\Cell\DateIntervalCell;
use OpenSpout\Common\Entity\Cell\DateTimeCell;
use OpenSpout\Common\Entity\Cell\EmptyCell;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Common\Entity\Cell\NumericCell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Comment\Comment;
use OpenSpout\Common\Entity\Style\Style;

abstract class Cell
{
    public ?Comment $comment = null;

    private Style $style;

    /**
     * @deprecated
     */
    public function __construct(?Style $style = null)
    {
        if (null !== $style) {
            $this->setStyle($style);
        }
    }

    abstract public function getValue(): bool|DateInterval|DateTimeInterface|float|int|string|null;

    final public function setStyle(Style $style): static
    {
        $this->style = $style;

        return $this;
    }

    final public function getStyle(): Style
    {
        return $this->style ?? new Style();
    }

    final public static function fromValue(bool|DateInterval|DateTimeInterface|float|int|string|null $value): self
    {
        if (\is_bool($value)) {
            return new BooleanCell($value);
        }
        if (null === $value || '' === $value) {
            return new EmptyCell($value);
        }
        if (\is_int($value) || \is_float($value)) {
            return new NumericCell($value);
        }
        if ($value instanceof DateTimeInterface) {
            return new DateTimeCell($value);
        }
        if ($value instanceof DateInterval) {
            return new DateIntervalCell($value);
        }
        if (isset($value[0]) && '=' === $value[0]) {
            return new FormulaCell($value, null);
        }

        return new StringCell($value);
    }
}
