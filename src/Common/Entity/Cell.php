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

    public function withStyle(Style $style): static
    {
        $new = clone $this;
        $new->style = $style;
        return $new;
    }

    abstract public function getValue(): null|bool|DateInterval|DateTimeInterface|float|int|string;

    final public function getStyle(): Style
    {
        return $this->style ?? new Style();
    }

    final public static function fromValue(null|bool|DateInterval|DateTimeInterface|float|int|string $value): self
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
