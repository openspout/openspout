<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Cell;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Comment\Comment;
use OpenSpout\Common\Entity\Style\Style;

final readonly class NumericCell extends Cell
{
    private float|int $value;

    public function __construct(
        float|int $value,
        ?Style $style = null,
        ?Comment $comment = null,
    ) {
        parent::__construct($style, $comment);
        $this->value = $value;
    }

    public function getValue(): float|int
    {
        return $this->value;
    }

    public function withValue(float|int $value): self
    {
        return new self($value, $this->style, $this->comment);
    }

    public function withStyle(Style $style): static
    {
        return new self($this->value, $style, $this->comment);
    }

    public function withoutStyle(): static
    {
        return new self($this->value, null, $this->comment);
    }

    public function withComment(Comment $comment): static
    {
        return new self($this->value, $this->style, $comment);
    }

    public function withoutComment(): static
    {
        return new self($this->value, $this->style, null);
    }
}
