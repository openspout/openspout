<?php

declare(strict_types=1);

namespace OpenSpout\Writer\ODS\Manager\Style;

use OpenSpout\Common\Entity\Style\Style;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class StyleRegistryTest extends TestCase
{
    public function testRegisterStyleKeepsTrackOfUsedFonts(): void
    {
        $styleRegistry = new StyleRegistry(new Style());

        self::assertCount(1, $styleRegistry->getUsedFonts(), 'There should only be the default font name');

        $styleRegistry->registerStyle(new Style(fontName: 'MyFont1'));
        $styleRegistry->registerStyle(new Style(fontName: 'MyFont2'));

        self::assertCount(3, $styleRegistry->getUsedFonts(), 'There should be 3 fonts registered');
    }
}
