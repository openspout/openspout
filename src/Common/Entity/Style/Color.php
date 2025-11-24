<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Style;

use OpenSpout\Common\Exception\InvalidColorException;

/**
 * This class provides constants and functions to work with colors.
 */
final readonly class Color
{
    /**
     * Standard colors - based on Office Online.
     */
    public const string BLACK = '000000';
    public const string WHITE = 'FFFFFF';
    public const string RED = 'FF0000';
    public const string DARK_RED = 'C00000';
    public const string ORANGE = 'FFC000';
    public const string YELLOW = 'FFFF00';
    public const string LIGHT_GREEN = '92D040';
    public const string GREEN = '00B050';
    public const string LIGHT_BLUE = '00B0E0';
    public const string BLUE = '0070C0';
    public const string DARK_BLUE = '002060';
    public const string PURPLE = '7030A0';

    /**
     * Returns an RGB color from R, G and B values.
     *
     * @param int<0, 255> $red   Red component, 0 - 255
     * @param int<0, 255> $green Green component, 0 - 255
     * @param int<0, 255> $blue  Blue component, 0 - 255
     *
     * @return non-empty-string RGB color
     */
    public static function rgb(int $red, int $green, int $blue): string
    {
        self::throwIfInvalidColorComponentValue($red);
        self::throwIfInvalidColorComponentValue($green);
        self::throwIfInvalidColorComponentValue($blue);

        return strtoupper(
            self::convertColorComponentToHex($red)
            .self::convertColorComponentToHex($green)
            .self::convertColorComponentToHex($blue)
        );
    }

    /**
     * Returns the ARGB color of the given RGB color,
     * assuming that alpha value is always 1.
     *
     * @param non-empty-string $rgbColor RGB color like "FF08B2"
     *
     * @return non-empty-string ARGB color
     */
    public static function toARGB(string $rgbColor): string
    {
        return 'FF'.$rgbColor;
    }

    /**
     * Throws an exception is the color component value is outside of bounds (0 - 255).
     *
     * @throws InvalidColorException
     */
    private static function throwIfInvalidColorComponentValue(int $colorComponent): void
    {
        if ($colorComponent < 0 || $colorComponent > 255) {
            throw new InvalidColorException("The RGB components must be between 0 and 255. Received: {$colorComponent}");
        }
    }

    /**
     * Converts the color component to its corresponding hexadecimal value.
     *
     * @param int<0, 255> $colorComponent Color component, 0 - 255
     *
     * @return non-empty-string Corresponding hexadecimal value, with a leading 0 if needed. E.g "0f", "2d"
     */
    private static function convertColorComponentToHex(int $colorComponent): string
    {
        return str_pad(dechex($colorComponent), 2, '0', STR_PAD_LEFT);
    }
}
