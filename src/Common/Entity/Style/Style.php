<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Style;

/**
 * Represents a style to be applied to a cell.
 */
final readonly class Style
{
    public const int DEFAULT_FONT_SIZE = 11;
    public const string DEFAULT_FONT_COLOR = Color::BLACK;
    public const string DEFAULT_FONT_NAME = 'Arial';

    public bool $shouldApplyFont;

    /**
     * @param non-negative-int      $fontSize
     * @param non-empty-string      $fontColor
     * @param non-empty-string      $fontName
     * @param null|non-empty-string $backgroundColor
     * @param null|non-empty-string $format
     */
    public function __construct(
        public bool $fontBold = false,
        public bool $fontItalic = false,
        public bool $fontUnderline = false,
        public bool $fontStrikethrough = false,
        public int $fontSize = self::DEFAULT_FONT_SIZE,
        public string $fontColor = self::DEFAULT_FONT_COLOR,
        public string $fontName = self::DEFAULT_FONT_NAME,
        public ?CellAlignment $cellAlignment = null,
        public ?CellVerticalAlignment $cellVerticalAlignment = null,
        public ?bool $shouldWrapText = null,
        public int $textRotation = 0,
        public ?bool $shouldShrinkToFit = null,
        public ?Border $border = null,
        public ?string $backgroundColor = null,
        public ?string $format = null,
    ) {
        $this->shouldApplyFont
            = $this->fontBold
            || $this->fontItalic
            || $this->fontUnderline
            || $this->fontStrikethrough
            || self::DEFAULT_FONT_SIZE !== $this->fontSize
            || self::DEFAULT_FONT_COLOR !== $this->fontColor
            || self::DEFAULT_FONT_NAME !== $this->fontName;
    }

    public function withFontBold(bool $fontBold): self
    {
        $values = get_object_vars($this);
        unset($values['shouldApplyFont']);
        $values['fontBold'] = $fontBold;

        return new self(...$values);
    }

    public function withFontItalic(bool $fontItalic): self
    {
        $values = get_object_vars($this);
        unset($values['shouldApplyFont']);
        $values['fontItalic'] = $fontItalic;

        return new self(...$values);
    }

    public function withFontUnderline(bool $fontUnderline): self
    {
        $values = get_object_vars($this);
        unset($values['shouldApplyFont']);
        $values['fontUnderline'] = $fontUnderline;

        return new self(...$values);
    }

    public function withFontStrikethrough(bool $fontStrikethrough): self
    {
        $values = get_object_vars($this);
        unset($values['shouldApplyFont']);
        $values['fontStrikethrough'] = $fontStrikethrough;

        return new self(...$values);
    }

    /**
     * @param int $fontSize Font size, in pixels
     */
    public function withFontSize(int $fontSize): self
    {
        $values = get_object_vars($this);
        unset($values['shouldApplyFont']);
        $values['fontSize'] = $fontSize;

        return new self(...$values);
    }

    /**
     * @param string $fontColor ARGB color (@see Color)
     */
    public function withFontColor(string $fontColor): self
    {
        $values = get_object_vars($this);
        unset($values['shouldApplyFont']);
        $values['fontColor'] = $fontColor;

        return new self(...$values);
    }

    public function withFontName(string $fontName): self
    {
        $values = get_object_vars($this);
        unset($values['shouldApplyFont']);
        $values['fontName'] = $fontName;

        return new self(...$values);
    }

    public function withCellAlignment(?CellAlignment $cellAlignment): self
    {
        $values = get_object_vars($this);
        unset($values['shouldApplyFont']);
        $values['cellAlignment'] = $cellAlignment;

        return new self(...$values);
    }

    public function withCellVerticalAlignment(?CellVerticalAlignment $cellVerticalAlignment): self
    {
        $values = get_object_vars($this);
        unset($values['shouldApplyFont']);
        $values['cellVerticalAlignment'] = $cellVerticalAlignment;

        return new self(...$values);
    }

    public function withShouldWrapText(bool $shouldWrapText): self
    {
        $values = get_object_vars($this);
        unset($values['shouldApplyFont']);
        $values['shouldWrapText'] = $shouldWrapText;

        return new self(...$values);
    }

    public function withTextRotation(int $textRotation): self
    {
        $values = get_object_vars($this);
        unset($values['shouldApplyFont']);
        $values['textRotation'] = $textRotation;

        return new self(...$values);
    }

    public function withShouldShrinkToFit(bool $shouldShrinkToFit): self
    {
        $values = get_object_vars($this);
        unset($values['shouldApplyFont']);
        $values['shouldShrinkToFit'] = $shouldShrinkToFit;

        return new self(...$values);
    }

    public function withBorder(Border $border): self
    {
        $values = get_object_vars($this);
        unset($values['shouldApplyFont']);
        $values['border'] = $border;

        return new self(...$values);
    }

    /**
     * @param string $color ARGB color (@see Color)
     */
    public function withBackgroundColor(string $color): self
    {
        $values = get_object_vars($this);
        unset($values['shouldApplyFont']);
        $values['backgroundColor'] = $color;

        return new self(...$values);
    }

    public function withFormat(string $format): self
    {
        $values = get_object_vars($this);
        unset($values['shouldApplyFont']);
        $values['format'] = $format;

        return new self(...$values);
    }
}
