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
use OpenSpout\Common\Entity\Cell\TextRunCell;
use OpenSpout\Common\Entity\Comment\Comment;
use OpenSpout\Common\Entity\Comment\TextRun;
use OpenSpout\Common\Entity\Style\Style;

abstract readonly class Cell
{
    public function __construct(
        public ?Style $style = null,
        public ?Comment $comment = null,
    ) {}

    /**
     * @return null|bool|DateInterval|DateTimeInterface|float|int|string|TextRun[]
     */
    abstract public function getValue(): array|bool|DateInterval|DateTimeInterface|float|int|string|null;

    abstract public function withStyle(Style $style): static;

    abstract public function withoutStyle(): static;

    abstract public function withComment(Comment $comment): static;

    abstract public function withoutComment(): static;

    /**
     * @param null|bool|DateInterval|DateTimeInterface|float|int|string|TextRun[] $value
     */
    final public static function fromValue(
        array|bool|DateInterval|DateTimeInterface|float|int|string|null $value,
        ?Style $style = null,
        ?Comment $comment = null,
    ): self {
        if (\is_array($value)) {
            return new TextRunCell($value, $style, $comment);
        }
        if (\is_bool($value)) {
            return new BooleanCell($value, $style, $comment);
        }
        if (null === $value || '' === $value) {
            return new EmptyCell($value, $style, $comment);
        }
        if (\is_int($value) || \is_float($value)) {
            return new NumericCell($value, $style, $comment);
        }
        if ($value instanceof DateTimeInterface) {
            return new DateTimeCell($value, $style, $comment);
        }
        if ($value instanceof DateInterval) {
            return new DateIntervalCell($value, $style, $comment);
        }
        if (isset($value[0]) && '=' === $value[0]) {
            return new FormulaCell($value, null, $style, $comment);
        }

        return new StringCell($value, $style, $comment);
    }
}
