<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Manager\Style;

use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderName;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class StyleRegistryTest extends TestCase
{
    public function testRegisterStyleAlsoRegistersFills(): void
    {
        $styleRegistry = new StyleRegistry(new Style());

        $styleBlack = new Style(backgroundColor: Color::BLACK);
        $styleOrange = new Style(backgroundColor: Color::ORANGE);
        $styleOrangeBold = new Style(backgroundColor: Color::ORANGE, fontBold: true);
        $styleNoBackgroundColor = new Style(fontItalic: true);

        $styleIdBlack = $styleRegistry->registerStyle($styleBlack);
        $styleIdOrange = $styleRegistry->registerStyle($styleOrange);
        $styleIdOrangeBold = $styleRegistry->registerStyle($styleOrangeBold);
        $styleIdNoBackgroundColor = $styleRegistry->registerStyle($styleNoBackgroundColor);

        self::assertCount(2, $styleRegistry->getRegisteredFills(), 'There should be 2 registered fills');

        self::assertSame(2, $styleRegistry->getFillIdForStyleId($styleIdBlack), 'First style with background color set should have index 2 (0 and 1 being reserved)');
        self::assertSame(3, $styleRegistry->getFillIdForStyleId($styleIdOrange), 'Second style with background color set - different from first style - should have index 3');
        self::assertSame(3, $styleRegistry->getFillIdForStyleId($styleIdOrangeBold), 'Style with background color already set should have the same index');
        self::assertSame(0, $styleRegistry->getFillIdForStyleId($styleIdNoBackgroundColor), 'Style with no background color should have index 0');
    }

    public function testRegisterStyleAlsoRegistersBorders(): void
    {
        $styleRegistry = new StyleRegistry(new Style());

        $borderLeft = new Border(new BorderPart(BorderName::LEFT));
        $borderRight = new Border(new BorderPart(BorderName::RIGHT));

        $styleBorderLeft = new Style(border: $borderLeft);
        $styleBorderRight = new Style(border: $borderRight);
        $styleBorderRightBold = new Style(border: $borderRight, fontBold: true);
        $styleNoBorder = new Style(fontItalic: true);

        $styleIdBorderLeft = $styleRegistry->registerStyle($styleBorderLeft);
        $styleIdBorderRight = $styleRegistry->registerStyle($styleBorderRight);
        $styleIdBorderRightBold = $styleRegistry->registerStyle($styleBorderRightBold);
        $styleIdBorderNo = $styleRegistry->registerStyle($styleNoBorder);

        self::assertCount(2, $styleRegistry->getRegisteredBorders(), 'There should be 2 registered borders');

        self::assertSame(1, $styleRegistry->getBorderIdForStyleId($styleIdBorderLeft), 'First style with border set should have index 1 (0 is for the default style)');
        self::assertSame(2, $styleRegistry->getBorderIdForStyleId($styleIdBorderRight), 'Second style with border set - different from first style - should have index 2');
        self::assertSame(2, $styleRegistry->getBorderIdForStyleId($styleIdBorderRightBold), 'Style with border already set should have the same index');
        self::assertSame(0, $styleRegistry->getBorderIdForStyleId($styleIdBorderNo), 'Style with no border should have index 0');
    }

    public function testRegisterStyleAlsoRegistersFormats(): void
    {
        $styleRegistry = new StyleRegistry(new Style());

        $styleBuiltinFormat = new Style(
            fontBold: true,
            format: '0.00',
        );
        $styleUserFormat = new Style(
            fontBold: true,
            format: '0.000',
        );
        $styleNoFormat = new Style(fontItalic: true);

        $styleIdBuiltin = $styleRegistry->registerStyle($styleBuiltinFormat);
        $styleIdUser = $styleRegistry->registerStyle($styleUserFormat);
        $styleIdNo = $styleRegistry->registerStyle($styleNoFormat);

        self::assertCount(2, $styleRegistry->getRegisteredFormats(), 'There should be 2 registered formats');

        self::assertSame(2, $styleRegistry->getFormatIdForStyleId($styleIdBuiltin), 'First style with builtin format set should have index 2 (0 is for the default style)');
        self::assertSame(164, $styleRegistry->getFormatIdForStyleId($styleIdUser), 'Second style with user format set should have index 164 (0 is for the default style)');
        self::assertSame(0, $styleRegistry->getFormatIdForStyleId($styleIdNo), 'Style with no format should have index 0');
    }
}
