<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Cell;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Comment\Comment;
use OpenSpout\Common\Entity\Comment\TextRun;
use OpenSpout\Common\Entity\Style\Style;

final readonly class TextRunCell extends Cell
{
    /** @var TextRun[] */
    private array $textRuns;

    /**
     * @param TextRun[] $textRuns
     */
    public function __construct(
        array $textRuns,
        ?Style $style,
        ?Comment $comment,
    ) {
        parent::__construct($style, $comment);
        $this->textRuns = $textRuns;
    }

    /**
     * @return TextRun[]
     */
    public function getValue(): array
    {
        return $this->textRuns;
    }

    public function getStringValue(): string
    {
        $value = '';
        foreach ($this->textRuns as $textRun) {
            $value .= $textRun->text;
        }

        return $value;
    }

    /**
     * @param TextRun ...$textRuns
     */
    public function withTextRuns(...$textRuns): self
    {
        return new self($textRuns, $this->style, $this->comment);
    }

    public function withStyle(Style $style): static
    {
        return new self($this->textRuns, $style, $this->comment);
    }

    public function withoutStyle(): static
    {
        return new self($this->textRuns, null, $this->comment);
    }

    public function withComment(Comment $comment): static
    {
        return new self($this->textRuns, $this->style, $comment);
    }

    public function withoutComment(): static
    {
        return new self($this->textRuns, $this->style, null);
    }
}
