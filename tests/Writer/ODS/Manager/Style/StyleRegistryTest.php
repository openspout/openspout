<?php

namespace OpenSpout\Writer\ODS\Manager\Style;

use OpenSpout\Writer\Common\Creator\Style\StyleBuilder;
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

        $style1 = (new StyleBuilder())->setFontName('MyFont1')->build();
        $styleRegistry->registerStyle($style1);

        $style2 = (new StyleBuilder())->setFontName('MyFont2')->build();
        $styleRegistry->registerStyle($style2);

        static::assertCount(3, $styleRegistry->getUsedFonts(), 'There should be 3 fonts registered');
    }

    /**
     * @return StyleRegistry
     */
    private function getStyleRegistry()
    {
        $defaultStyle = (new StyleBuilder())->build();

        return new StyleRegistry($defaultStyle);
    }
}
