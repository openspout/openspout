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
        $part = BorderPart::create(BorderName::LEFT);
        $newPart = $part->withName(BorderName::RIGHT);

        self::assertSame(BorderName::RIGHT, $newPart->name);
        self::assertSame(BorderName::LEFT, $part->name);
    }

    public function testBorderPartWithColor(): void
    {
        $part = BorderPart::create(BorderName::LEFT);
        $newPart = $part->withColor(Color::RED);

        self::assertSame(Color::RED, $newPart->color);
        self::assertSame(Color::BLACK, $part->color);
    }

    public function testBorderPartWithWidth(): void
    {
        $part = BorderPart::create(BorderName::LEFT)->withWidth(BorderWidth::THIN);
        $newPart = $part->withWidth(BorderWidth::THICK);

        self::assertSame(BorderWidth::THICK, $newPart->width);
        self::assertSame(BorderWidth::THIN, $part->width);
    }

    public function testBorderPartWithStyle(): void
    {
        $part = BorderPart::create(BorderName::LEFT);
        $newPart = $part->withStyle(BorderStyle::DASHED);

        self::assertSame(BorderStyle::DASHED, $newPart->style);
        self::assertSame(BorderStyle::SOLID, $part->style);
    }

    public function testBorderPartCreate(): void
    {
        $part = BorderPart::create(BorderName::LEFT);

        self::assertSame(BorderName::LEFT, $part->name);
        self::assertSame(Color::BLACK, $part->color);
        self::assertSame(BorderWidth::MEDIUM, $part->width);
        self::assertSame(BorderStyle::SOLID, $part->style);
    }
}
