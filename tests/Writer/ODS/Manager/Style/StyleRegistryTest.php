<?php

namespace OpenSpout\Writer\ODS\Manager\Style;

use OpenSpout\Common\Entity\Style\Style;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class StyleRegistryTest extends TestCase
{
    public function testRegisterStyleKeepsTrackOfUsedFonts()
    {
        $styleRegistry = $this->getStyleRegistry();

        static::assertCount(1, $styleRegistry->getUsedFonts(), 'There should only be the default font name');

        $style1 = (new Style())->setFontName('MyFont1');
        $styleRegistry->registerStyle($style1);

        $style2 = (new Style())->setFontName('MyFont2');
        $styleRegistry->registerStyle($style2);

        static::assertCount(3, $styleRegistry->getUsedFonts(), 'There should be 3 fonts registered');
    }

    /**
     * @return StyleRegistry
     */
    private function getStyleRegistry()
    {
        $defaultStyle = (new Style());

        return new StyleRegistry($defaultStyle);
    }
}
