<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Comment;

use OpenSpout\Common\Entity\Style\TextRunVerticalStyle;

/**
 * This class defines rich text in a fluent interface that can be added to a comment.
 */
final readonly class TextRun
{
    public const int DEFAULT_FONT_SIZE = 10;
    public const string DEFAULT_FONT_COLOR = '000000';
    public const string DEFAULT_FONT_NAME = 'Tahoma';

    public function __construct(
        public string $text,
        public int $fontSize = self::DEFAULT_FONT_SIZE,
        public string $fontColor = self::DEFAULT_FONT_COLOR,
        public string $fontName = self::DEFAULT_FONT_NAME,
        public bool $bold = false,
        public bool $italic = false,
        public ?TextRunVerticalStyle $verticalStyle = null,
    ) {}

    public function withSuperscript(): self
    {
        return new self($this->text, $this->fontSize, $this->fontColor, $this->fontName, $this->bold, $this->italic, TextRunVerticalStyle::SUPERSCRIPT);
    }

    public function withSubscript(): self
    {
        return new self($this->text, $this->fontSize, $this->fontColor, $this->fontName, $this->bold, $this->italic, TextRunVerticalStyle::SUBSCRIPT);
    }

    public function withoutVerticalStyle(): self
    {
        return new self($this->text, $this->fontSize, $this->fontColor, $this->fontName, $this->bold, $this->italic, null);
    }

    public function withText(string $text): self
    {
        return new self($text, $this->fontSize, $this->fontColor, $this->fontName, $this->bold, $this->italic, $this->verticalStyle);
    }

    public function withFontSize(int $fontSize): self
    {
        return new self($this->text, $fontSize, $this->fontColor, $this->fontName, $this->bold, $this->italic, $this->verticalStyle);
    }

    public function withFontColor(string $fontColor): self
    {
        return new self($this->text, $this->fontSize, $fontColor, $this->fontName, $this->bold, $this->italic, $this->verticalStyle);
    }

    public function withFontName(string $fontName): self
    {
        return new self($this->text, $this->fontSize, $this->fontColor, $fontName, $this->bold, $this->italic, $this->verticalStyle);
    }

    public function withBold(bool $bold): self
    {
        return new self($this->text, $this->fontSize, $this->fontColor, $this->fontName, $bold, $this->italic, $this->verticalStyle);
    }

    public function withItalic(bool $italic): self
    {
        return new self($this->text, $this->fontSize, $this->fontColor, $this->fontName, $this->bold, $italic, $this->verticalStyle);
    }
}
