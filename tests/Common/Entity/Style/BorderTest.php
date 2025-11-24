<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Style;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class BorderTest extends TestCase
{
    public function testNotMoreThanFourPartsPossible(): void
    {
        $border = new Border(
            new BorderPart(BorderName::LEFT),
            new BorderPart(BorderName::RIGHT),
            new BorderPart(BorderName::TOP),
            new BorderPart(BorderName::BOTTOM),
            new BorderPart(BorderName::LEFT),
        );

        self::assertCount(4, $border->getParts(), 'There should never be more than 4 border parts');
    }

    public function testAnyCombinationOfAllowedBorderPartsParams(): void
    {
        $color = Color::BLACK;
        foreach (BorderName::cases() as $allowedName) {
            foreach (BorderStyle::cases() as $allowedStyle) {
                foreach (BorderWidth::cases() as $allowedWidth) {
                    $border = new Border(new BorderPart($allowedName, $color, $allowedWidth, $allowedStyle));
                    self::assertCount(1, $border->getParts());

                    $part = $border->getParts()[$allowedName->value];

                    self::assertSame($allowedStyle, $part->style);
                    self::assertSame($allowedWidth, $part->width);
                    self::assertSame($color, $part->color);
                }
            }
        }
    }
}
