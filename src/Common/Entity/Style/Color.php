<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Style;

use OpenSpout\Common\Exception\InvalidColorException;

/**
 * This class provides constants and functions to work with colors.
 */
final class Color
{
    /**
     * Standard colors - based on Office Online.
     */
    public const BLACK = '000000';
    public const WHITE = 'FFFFFF';
    public const RED = 'FF0000';
    public const DARK_RED = 'C00000';
    public const ORANGE = 'FFC000';
    public const YELLOW = 'FFFF00';
    public const LIGHT_GREEN = '92D040';
    public const GREEN = '00B050';
    public const LIGHT_BLUE = '00B0E0';
    public const BLUE = '0070C0';
    public const DARK_BLUE = '002060';
    public const PURPLE = '7030A0';

    // From: https://learn.microsoft.com/en-us/dotnet/api/documentformat.openxml.spreadsheet.indexedcolors?view=openxml-2.8.1
    private const INDEXED_COLORS = [
        '00000000','00FFFFFF','00FF0000','0000FF00','000000FF','00FFFF00','00FF00FF','0000FFFF',
        '00000000','00FFFFFF','00FF0000','0000FF00','000000FF','00FFFF00','00FF00FF','0000FFFF',
        '00800000','00008000','00000080','00808000','00800080','00008080','00C0C0C0','00808080',
        '009999FF','00993366','00FFFFCC','00CCFFFF','00660066','00FF8080','000066CC','00CCCCFF',
        '00000080','00FF00FF','00FFFF00','0000FFFF','00800080','00800000','00008080','000000FF',
        '0000CCFF','00CCFFFF','00CCFFCC','00FFFF99','0099CCFF','00FF99CC','00CC99FF','00FFCC99',
        '003366FF','0033CCCC','0099CC00','00FFCC00','00FF9900','00FF6600','00666699','00969696',
        '00003366','00339966','00003300','00333300','00993300','00993366','00333399','00333333',
        '00000000', // 64 = System Foreground color, defaulting to 'black'
        '00FFFFFF', // 65 = System Background color, defaulting to 'white'
    ];

    public static function indexedColor($index)
    {
        return self::INDEXED_COLORS[$index];
    }

    /**
     * Returns an RGB color from R, G and B values.
     *
     * @param int $red   Red component, 0 - 255
     * @param int $green Green component, 0 - 255
     * @param int $blue  Blue component, 0 - 255
     *
     * @return string RGB color
     */
    public static function rgb(int $red, int $green, int $blue): string
    {
        self::throwIfInvalidColorComponentValue($red);
        self::throwIfInvalidColorComponentValue($green);
        self::throwIfInvalidColorComponentValue($blue);

        return strtoupper(
            self::convertColorComponentToHex($red).
            self::convertColorComponentToHex($green).
            self::convertColorComponentToHex($blue)
        );
    }

    /**
     * Returns the ARGB color of the given RGB color,
     * assuming that alpha value is always 1.
     *
     * @param string $rgbColor RGB color like "FF08B2"
     *
     * @return string ARGB color
     */
    public static function toARGB(string $rgbColor): string
    {
        return 'FF'.$rgbColor;
    }

    /**
     * Throws an exception is the color component value is outside of bounds (0 - 255).
     *
     * @throws \OpenSpout\Common\Exception\InvalidColorException
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
     * @param int $colorComponent Color component, 0 - 255
     *
     * @return string Corresponding hexadecimal value, with a leading 0 if needed. E.g "0f", "2d"
     */
    private static function convertColorComponentToHex(int $colorComponent): string
    {
        return str_pad(dechex($colorComponent), 2, '0', STR_PAD_LEFT);
    }
}
