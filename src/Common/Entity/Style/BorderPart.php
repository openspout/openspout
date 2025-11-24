<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Style;

final readonly class BorderPart
{
    /**
     * @param non-empty-string $color A RGB color code
     */
    public function __construct(
        public BorderName $name,
        public string $color = Color::BLACK,
        public BorderWidth $width = BorderWidth::MEDIUM,
        public BorderStyle $style = BorderStyle::SOLID,
    ) {}
}
