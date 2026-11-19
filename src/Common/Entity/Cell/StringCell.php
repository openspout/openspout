<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Cell;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Comment\Comment;
use OpenSpout\Common\Entity\Hyperlink;
use OpenSpout\Common\Entity\Style\Style;

final readonly class StringCell extends Cell
{
    private string $value;

    public function __construct(
        string $value,
        ?Style $style = null,
        ?Comment $comment = null,
        Hyperlink|string|null $hyperlinkUrl = null,
    ) {
        parent::__construct($style, $comment, $hyperlinkUrl);
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function withValue(string $value): self
    {
        return new self($value, $this->style, $this->comment, $this->hyperlink);
    }

    public function withStyle(Style $style): static
    {
        return new self($this->value, $style, $this->comment, $this->hyperlink);
    }

    public function withoutStyle(): static
    {
        return new self($this->value, null, $this->comment, $this->hyperlink);
    }

    public function withComment(Comment $comment): static
    {
        return new self($this->value, $this->style, $comment, $this->hyperlink);
    }

    public function withoutComment(): static
    {
        return new self($this->value, $this->style, null, $this->hyperlink);
    }

    public function withHyperlink(Hyperlink|string $hyperlinkUrl): static
    {
        return new self($this->value, $this->style, $this->comment, $hyperlinkUrl);
    }

    public function withoutHyperlink(): static
    {
        return new self($this->value, $this->style, $this->comment, null);
    }
}
