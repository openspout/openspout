<?php

declare(strict_types=1);

namespace OpenSpout\Writer\ODS\Helper;

use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\BorderStyle;
use OpenSpout\Common\Entity\Style\BorderWidth;

/**
 * The fo:border, fo:border-top, fo:border-bottom, fo:border-left and fo:border-right attributes
 * specify border properties
 * http://docs.oasis-open.org/office/v1.2/os/OpenDocument-v1.2-os-part1.html#__RefHeading__1419780_253892949.
 *
 * Example table-cell-properties
 *
 * <style:table-cell-properties
 * fo:border-bottom="0.74pt solid #ffc000" style:diagonal-bl-tr="none"
 * style:diagonal-tl-br="none" fo:border-left="none" fo:border-right="none"
 * style:rotation-align="none" fo:border-top="none"/>
 *
 * @internal
 */
final readonly class BorderHelper
{
    public const array widthMap = [
        BorderWidth::THIN->name => '0.75pt',
        BorderWidth::MEDIUM->name => '1.75pt',
        BorderWidth::THICK->name => '2.5pt',
    ];

    public const array styleMap = [
        BorderStyle::SOLID->name => 'solid',
        BorderStyle::DASHED->name => 'dashed',
        BorderStyle::DOTTED->name => 'dotted',
        BorderStyle::DOUBLE->name => 'double',
    ];

    public static function serializeBorderPart(BorderPart $borderPart): string
    {
        $definition = 'fo:border-%s="%s"';

        if (BorderStyle::NONE === $borderPart->style) {
            $borderPartDefinition = \sprintf($definition, $borderPart->name->value, 'none');
        } else {
            $attributes = [
                self::widthMap[$borderPart->width->name],
                self::styleMap[$borderPart->style->name],
                '#'.$borderPart->color,
            ];
            $borderPartDefinition = \sprintf($definition, $borderPart->name->value, implode(' ', $attributes));
        }

        return $borderPartDefinition;
    }
}
