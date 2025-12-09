<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Style;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class BorderPartTest extends TestCase
{
    public function testBorderPartWithName(): void
    {
        $part = new BorderPart(BorderName::LEFT);
        $newPart = $part->withName(BorderName::RIGHT);

        self::assertSame(BorderName::RIGHT, $newPart->name);
        self::assertSame(BorderName::LEFT, $part->name);
    }

    public function testBorderPartWithColor(): void
    {
        $part = new BorderPart(BorderName::LEFT, Color::BLACK);
        $newPart = $part->withColor(Color::RED);

        self::assertSame(Color::RED, $newPart->color);
        self::assertSame(Color::BLACK, $part->color);
    }

    public function testBorderPartWithWidth(): void
    {
        $part = new BorderPart(BorderName::LEFT, Color::BLACK, BorderWidth::THIN);
        $newPart = $part->withWidth(BorderWidth::THICK);

        self::assertSame(BorderWidth::THICK, $newPart->width);
        self::assertSame(BorderWidth::THIN, $part->width);
    }

    public function testBorderPartWithStyle(): void
    {
        $part = new BorderPart(BorderName::LEFT, Color::BLACK, BorderWidth::MEDIUM, BorderStyle::SOLID);
        $newPart = $part->withStyle(BorderStyle::DASHED);

        self::assertSame(BorderStyle::DASHED, $newPart->style);
        self::assertSame(BorderStyle::SOLID, $part->style);
    }
}
