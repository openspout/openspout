<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Helper;

use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\BorderStyle;
use OpenSpout\Common\Entity\Style\BorderWidth;

/**
 * @internal
 */
final readonly class BorderHelper
{
    private const array xlsxStyleMap = [
        BorderStyle::SOLID->name => [
            BorderWidth::THIN->name => 'thin',
            BorderWidth::MEDIUM->name => 'medium',
            BorderWidth::THICK->name => 'thick',
        ],
        BorderStyle::DOTTED->name => [
            BorderWidth::THIN->name => 'dotted',
            BorderWidth::MEDIUM->name => 'dotted',
            BorderWidth::THICK->name => 'dotted',
        ],
        BorderStyle::DASHED->name => [
            BorderWidth::THIN->name => 'dashed',
            BorderWidth::MEDIUM->name => 'mediumDashed',
            BorderWidth::THICK->name => 'mediumDashed',
        ],
        BorderStyle::DOUBLE->name => [
            BorderWidth::THIN->name => 'double',
            BorderWidth::MEDIUM->name => 'double',
            BorderWidth::THICK->name => 'double',
        ],
        BorderStyle::NONE->name => [
            BorderWidth::THIN->name => 'none',
            BorderWidth::MEDIUM->name => 'none',
            BorderWidth::THICK->name => 'none',
        ],
    ];

    public static function serializeBorderPart(?BorderPart $borderPart): string
    {
        if (null === $borderPart) {
            return '';
        }

        $borderStyle = self::getBorderStyle($borderPart);

        $colorEl = \sprintf('<color rgb="%s"/>', $borderPart->color);
        $partEl = \sprintf(
            '<%s style="%s">%s</%s>',
            $borderPart->name->value,
            $borderStyle,
            $colorEl,
            $borderPart->name->value,
        );

        return $partEl.PHP_EOL;
    }

    /**
     * Get the style definition from the style map.
     */
    private static function getBorderStyle(BorderPart $borderPart): string
    {
        return self::xlsxStyleMap[$borderPart->style->name][$borderPart->width->name];
    }
}
