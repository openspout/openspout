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

    public function testBorderWithBorderPart(): void
    {
        $leftPart = new BorderPart(BorderName::LEFT, Color::BLACK);
        $rightPart = new BorderPart(BorderName::RIGHT, Color::RED);
        $border = new Border($leftPart);
        $newBorder = $border->withBorderPart($rightPart);

        self::assertCount(2, $newBorder->getParts());
        self::assertSame(Color::RED, $newBorder->getPart(BorderName::RIGHT)?->color);
        self::assertCount(1, $border->getParts());
    }

    public function testBorderWithBorderPartReplacement(): void
    {
        $leftPart1 = new BorderPart(BorderName::LEFT, Color::BLACK);
        $leftPart2 = new BorderPart(BorderName::LEFT, Color::BLUE);
        $border = new Border($leftPart1);
        $newBorder = $border->withBorderPart($leftPart2);

        self::assertCount(1, $newBorder->getParts());
        self::assertSame(Color::BLUE, $newBorder->getPart(BorderName::LEFT)?->color);
        self::assertSame(Color::BLACK, $border->getPart(BorderName::LEFT)?->color);
    }

    public function testBorderWithoutBorder(): void
    {
        $leftPart = new BorderPart(BorderName::LEFT);
        $rightPart = new BorderPart(BorderName::RIGHT);
        $border = new Border($leftPart, $rightPart);
        $newBorder = $border->withoutBorder(BorderName::LEFT);

        self::assertCount(1, $newBorder->getParts());
        self::assertNull($newBorder->getPart(BorderName::LEFT));
        self::assertNotNull($newBorder->getPart(BorderName::RIGHT));
        self::assertCount(2, $border->getParts());
    }

    public function testBorderWithBorderParts(): void
    {
        $leftPart = new BorderPart(BorderName::LEFT, Color::BLACK);
        $border = new Border($leftPart);

        $rightPart = new BorderPart(BorderName::RIGHT, Color::RED);
        $topPart = new BorderPart(BorderName::TOP, Color::BLUE);
        $newBorder = $border->withBorderParts($rightPart, $topPart);

        self::assertCount(3, $newBorder->getParts());
        self::assertNotNull($newBorder->getPart(BorderName::LEFT));
        self::assertNotNull($newBorder->getPart(BorderName::RIGHT));
        self::assertNotNull($newBorder->getPart(BorderName::TOP));
        self::assertCount(1, $border->getParts());
    }
}
