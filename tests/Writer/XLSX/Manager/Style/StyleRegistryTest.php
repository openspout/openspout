<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Manager\Style;

use OpenSpout\Common\Entity\Style\Border;
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
        $styleRegistry = $this->getStyleRegistry();

        $styleBlack = (new Style())->setBackgroundColor(Color::BLACK);
        $styleOrange = (new Style())->setBackgroundColor(Color::ORANGE);
        $styleOrangeBold = (new Style())->setBackgroundColor(Color::ORANGE)->setFontBold();
        $styleNoBackgroundColor = (new Style())->setFontItalic();

        $styleRegistry->registerStyle($styleBlack);
        $styleRegistry->registerStyle($styleOrange);
        $styleRegistry->registerStyle($styleOrangeBold);
        $styleRegistry->registerStyle($styleNoBackgroundColor);

        self::assertCount(2, $styleRegistry->getRegisteredFills(), 'There should be 2 registered fills');

        self::assertSame(2, $styleRegistry->getFillIdForStyleId($styleBlack->getId()), 'First style with background color set should have index 2 (0 and 1 being reserved)');
        self::assertSame(3, $styleRegistry->getFillIdForStyleId($styleOrange->getId()), 'Second style with background color set - different from first style - should have index 3');
        self::assertSame(3, $styleRegistry->getFillIdForStyleId($styleOrangeBold->getId()), 'Style with background color already set should have the same index');
        self::assertSame(0, $styleRegistry->getFillIdForStyleId($styleNoBackgroundColor->getId()), 'Style with no background color should have index 0');
    }

    public function testRegisterStyleAlsoRegistersBorders(): void
    {
        $styleRegistry = $this->getStyleRegistry();

        $borderLeft = new Border(new BorderPart(Border::LEFT));
        $borderRight = new Border(new BorderPart(Border::RIGHT));

        $styleBorderLeft = (new Style())->setBorder($borderLeft);
        $styleBorderRight = (new Style())->setBorder($borderRight);
        $styleBorderRightBold = (new Style())->setBorder($borderRight)->setFontBold();
        $styleNoBorder = (new Style())->setFontItalic();

        $styleRegistry->registerStyle($styleBorderLeft);
        $styleRegistry->registerStyle($styleBorderRight);
        $styleRegistry->registerStyle($styleBorderRightBold);
        $styleRegistry->registerStyle($styleNoBorder);

        self::assertCount(2, $styleRegistry->getRegisteredBorders(), 'There should be 2 registered borders');

        self::assertSame(1, $styleRegistry->getBorderIdForStyleId($styleBorderLeft->getId()), 'First style with border set should have index 1 (0 is for the default style)');
        self::assertSame(2, $styleRegistry->getBorderIdForStyleId($styleBorderRight->getId()), 'Second style with border set - different from first style - should have index 2');
        self::assertSame(2, $styleRegistry->getBorderIdForStyleId($styleBorderRightBold->getId()), 'Style with border already set should have the same index');
        self::assertSame(0, $styleRegistry->getBorderIdForStyleId($styleNoBorder->getId()), 'Style with no border should have index 0');
    }

    public function testRegisterStyleAlsoRegistersFormats(): void
    {
        $styleRegistry = $this->getStyleRegistry();

        $styleBuiltinFormat = (new Style())
            ->setFontBold()
            ->setFormat('0.00')// Builtin format
        ;

        $styleUserFormat = (new Style())
            ->setFontBold()
            ->setFormat('0.000')
        ;
        $styleNoFormat = (new Style())->setFontItalic();

        $styleRegistry->registerStyle($styleBuiltinFormat);
        $styleRegistry->registerStyle($styleUserFormat);
        $styleRegistry->registerStyle($styleNoFormat);

        self::assertCount(2, $styleRegistry->getRegisteredFormats(), 'There should be 2 registered formats');

        self::assertSame(2, $styleRegistry->getFormatIdForStyleId($styleBuiltinFormat->getId()), 'First style with builtin format set should have index 2 (0 is for the default style)');
        self::assertSame(164, $styleRegistry->getFormatIdForStyleId($styleUserFormat->getId()), 'Second style with user format set should have index 164 (0 is for the default style)');

        self::assertSame(0, $styleRegistry->getFormatIdForStyleId($styleNoFormat->getId()), 'Style with no format should have index 0');
    }

    private function getStyleRegistry(): StyleRegistry
    {
        $defaultStyle = (new Style());

        return new StyleRegistry($defaultStyle);
    }
}
