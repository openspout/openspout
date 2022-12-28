<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Comment;

/**
 * This class defines a comment that can be added to a cell.
 */
final class Comment
{
    /**
     * The textruns for this comment.
     *
     * @var TextRun[]
     */
    private array $paragraphs = [];

    /** Comment height (CSS style, i.e. XXpx or YYpt). */
    private string $height = '55.5pt';

    /** Comment width (CSS style, i.e. XXpx or YYpt). */
    private string $width = '96pt';

    /** Left margin (CSS style, i.e. XXpx or YYpt). */
    private string $marginLeft = '59.25pt';

    /** Top margin (CSS style, i.e. XXpx or YYpt). */
    private string $marginTop = '1.5pt';

    /** Visible. */
    private bool $visible = false;

    /** Comment fill color. */
    private string $fillColor = '#FFFFE1';

    public function __construct()
    {
    }

    public function addTextRun(?TextRun $text): self
    {
        $this->paragraphs[] = $text;

        return $this;
    }

    public function createTextRun(string $text): TextRun
    {
        $paragraph = new TextRun($text);
        $this->paragraphs[] = $paragraph;

        return $paragraph;
    }

    public function setWidth(string $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function setHeight(string $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function setMarginTop(string $marginTop): self
    {
        $this->marginTop = $marginTop;

        return $this;
    }

    public function setMarginLeft(string $marginLeft): self
    {
        $this->marginLeft = $marginLeft;

        return $this;
    }

    public function setVisible(bool $visible): self
    {
        $this->visible = $visible;

        return $this;
    }

    public function setFillColor(string $fillColor): self
    {
        $this->fillColor = $fillColor;

        return $this;
    }

    /**
     * The textruns for this comment.
     *
     * @return TextRun[]
     */
    public function getTextRuns(): array
    {
        return $this->paragraphs;
    }

    public function getHeight(): string
    {
        return $this->height;
    }

    public function getWidth(): string
    {
        return $this->width;
    }

    public function getMarginTop(): string
    {
        return $this->marginTop;
    }

    public function getMarginLeft(): string
    {
        return $this->marginLeft;
    }

    public function getVisible(): bool
    {
        return $this->visible;
    }

    public function getFillColor(): string
    {
        return $this->fillColor;
    }
}
