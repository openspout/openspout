<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Comment;

use InvalidArgumentException;

/**
 * This class defines a comment that can be added to a cell.
 */
final readonly class Comment
{
    public const string DEFAULT_HEIGHT = '55.5pt';
    public const string DEFAULT_WIDTH = '96pt';
    public const string DEFAULT_MARGIN_LEFT = '59.25pt';
    public const string DEFAULT_MARGIN_TOP = '1.5pt';
    public const string DEFAULT_FILL_COLOR = '#FFFFE1';

    /**
     * @param list<TextRun> $textRuns
     */
    public function __construct(
        public string $height = self::DEFAULT_HEIGHT,
        public string $width = self::DEFAULT_WIDTH,
        public string $marginLeft = self::DEFAULT_MARGIN_LEFT,
        public string $marginTop = self::DEFAULT_MARGIN_TOP,
        public bool $visible = false,
        public string $fillColor = self::DEFAULT_FILL_COLOR,
        public array $textRuns = [],
    ) {
        foreach ($this->textRuns as $index => $textRun) {
            if (!$textRun instanceof TextRun) {
                throw new InvalidArgumentException(\sprintf(
                    'TextRuns must be instance of %s, %s provided at index %s',
                    TextRun::class,
                    get_debug_type($textRun),
                    $index
                ));
            }
        }
    }

    public function withHeight(string $height): self
    {
        return new self(
            $height,
            $this->width,
            $this->marginLeft,
            $this->marginTop,
            $this->visible,
            $this->fillColor,
            $this->textRuns
        );
    }

    public function withWidth(string $width): self
    {
        return new self(
            $this->height,
            $width,
            $this->marginLeft,
            $this->marginTop,
            $this->visible,
            $this->fillColor,
            $this->textRuns
        );
    }

    public function withMarginLeft(string $marginLeft): self
    {
        return new self(
            $this->height,
            $this->width,
            $marginLeft,
            $this->marginTop,
            $this->visible,
            $this->fillColor,
            $this->textRuns
        );
    }

    public function withMarginTop(string $marginTop): self
    {
        return new self(
            $this->height,
            $this->width,
            $this->marginLeft,
            $marginTop,
            $this->visible,
            $this->fillColor,
            $this->textRuns
        );
    }

    public function withVisible(bool $visible): self
    {
        return new self(
            $this->height,
            $this->width,
            $this->marginLeft,
            $this->marginTop,
            $visible,
            $this->fillColor,
            $this->textRuns
        );
    }

    public function withFillColor(string $fillColor): self
    {
        return new self(
            $this->height,
            $this->width,
            $this->marginLeft,
            $this->marginTop,
            $this->visible,
            $fillColor,
            $this->textRuns
        );
    }

    /**
     * @param list<TextRun> $textRuns
     */
    public function withTextRuns(array $textRuns): self
    {
        return new self(
            $this->height,
            $this->width,
            $this->marginLeft,
            $this->marginTop,
            $this->visible,
            $this->fillColor,
            $textRuns
        );
    }
}
