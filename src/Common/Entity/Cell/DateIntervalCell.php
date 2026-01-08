<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Cell;

use DateInterval;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Comment\Comment;
use OpenSpout\Common\Entity\Style\Style;

final readonly class DateIntervalCell extends Cell
{
    private DateInterval $value;

    /**
     * For Excel make sure to set a format onto the style (Style::setFormat()) with the left most unit enclosed with
     *   brackets: '[h]:mm', '[hh]:mm:ss', '[m]:ss', '[s]', etc.
     * This makes sure excel knows what to do with the remaining time that exceeds this unit. Without brackets Excel
     *   will interpret the value as date time and not duration if it is greater or equal 1.
     */
    public function __construct(
        DateInterval $value,
        ?Style $style = null,
        ?Comment $comment = null,
    ) {
        parent::__construct($style, $comment);
        $this->value = $value;
    }

    public function getValue(): DateInterval
    {
        return $this->value;
    }

    public function withValue(DateInterval $value): self
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
