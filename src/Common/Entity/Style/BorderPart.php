<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Style;

final readonly class BorderPart
{
    public const string DEFAULT_COLOR = Color::BLACK;
    public const BorderWidth DEFAULT_WIDTH = BorderWidth::MEDIUM;
    public const BorderStyle DEFAULT_STYLE = BorderStyle::SOLID;

    /**
     * @param non-empty-string $color A RGB color code
     */
    public function __construct(
        public BorderName $name,
        public string $color = self::DEFAULT_COLOR,
        public BorderWidth $width = self::DEFAULT_WIDTH,
        public BorderStyle $style = self::DEFAULT_STYLE,
    ) {}

    public function withName(BorderName $name): self
    {
        return new self($name, $this->color, $this->width, $this->style);
    }

    /**
     * @param non-empty-string $color A RGB color code
     */
    public function withColor(string $color): self
    {
        return new self($this->name, $color, $this->width, $this->style);
    }

    public function withWidth(BorderWidth $width): self
    {
        return new self($this->name, $this->color, $width, $this->style);
    }

    public function withStyle(BorderStyle $style): self
    {
        return new self($this->name, $this->color, $this->width, $style);
    }
}
