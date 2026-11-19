<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Cell;

use DateInterval;
use DateTimeImmutable;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Comment\Comment;
use OpenSpout\Common\Entity\Hyperlink;
use OpenSpout\Common\Entity\Style\Style;

final readonly class FormulaCell extends Cell
{
    public function __construct(
        private string $value,
        private bool|DateInterval|DateTimeImmutable|float|int|string|null $computedValue = null,
        ?Style $style = null,
        ?Comment $comment = null,
        Hyperlink|string|null $hyperlink = null,
    ) {
        parent::__construct($style, $comment, $hyperlink);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getComputedValue(): bool|DateInterval|DateTimeImmutable|float|int|string|null
    {
        return $this->computedValue;
    }

    public function withValue(string $value): self
    {
        return new self($value, $this->computedValue, $this->style, $this->comment, $this->hyperlink);
    }

    public function withComputedValue(bool|DateInterval|DateTimeImmutable|float|int|string|null $computedValue): self
    {
        return new self($this->value, $computedValue, $this->style, $this->comment, $this->hyperlink);
    }

    public function withStyle(Style $style): static
    {
        return new self($this->value, $this->computedValue, $style, $this->comment, $this->hyperlink);
    }

    public function withoutStyle(): static
    {
        return new self($this->value, $this->computedValue, null, $this->comment, $this->hyperlink);
    }

    public function withComment(Comment $comment): static
    {
        return new self($this->value, $this->computedValue, $this->style, $comment, $this->hyperlink);
    }

    public function withoutComment(): static
    {
        return new self($this->value, $this->computedValue, $this->style, null, $this->hyperlink);
    }

    public function withHyperlink(Hyperlink|string $hyperlink): static
    {
        return new self($this->value, $this->computedValue, $this->style, $this->comment, $hyperlink);
    }

    public function withoutHyperlink(): static
    {
        return new self($this->value, $this->computedValue, $this->style, $this->comment, null);
    }
}
