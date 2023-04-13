<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Style;

use OpenSpout\Common\Exception\InvalidColorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ColorTest extends TestCase
{
    public static function dataProviderForTestRGB(): array
    {
        return [
            [0, 0, 0, Color::BLACK],
            [255, 255, 255, Color::WHITE],
            [255, 0, 0, Color::RED],
            [192, 0, 0, Color::DARK_RED],
            [255, 192, 0, Color::ORANGE],
            [255, 255, 0, Color::YELLOW],
            [146, 208, 64, Color::LIGHT_GREEN],
            [0, 176, 80, Color::GREEN],
            [0, 176, 224, Color::LIGHT_BLUE],
            [0, 112, 192, Color::BLUE],
            [0, 32, 96, Color::DARK_BLUE],
            [112, 48, 160, Color::PURPLE],
            [0, 0, 0, '000000'],
            [255, 255, 255, 'FFFFFF'],
            [255, 0, 0, 'FF0000'],
            [0, 128, 0, '008000'],
            [0, 255, 0, '00FF00'],
            [0, 0, 255, '0000FF'],
            [128, 22, 43, '80162B'],
        ];
    }

    #[DataProvider('dataProviderForTestRGB')]
    public function testRGB(int $red, int $green, int $blue, string $expectedColor): void
    {
        $color = Color::rgb($red, $green, $blue);
        self::assertSame($expectedColor, $color);
    }

    public static function dataProviderForTestRGBAInvalidColorComponents(): array
    {
        return [
            [-1, 0, 0],
            [0, -1, 0],
            [0, 0, -1],
            [999, 0, 0],
            [0, 999, 0],
            [0, 0, 999],
        ];
    }

    #[DataProvider('dataProviderForTestRGBAInvalidColorComponents')]
    public function testRGBInvalidColorComponents(int $red, int $green, int $blue): void
    {
        $this->expectException(InvalidColorException::class);

        Color::rgb($red, $green, $blue);
    }
}
