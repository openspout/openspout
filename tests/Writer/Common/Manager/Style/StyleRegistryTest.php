<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common\Manager\Style;

use OpenSpout\Common\Entity\Style\Style;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class StyleRegistryTest extends TestCase
{
    private AbstractStyleRegistry $styleRegistry;

    protected function setUp(): void
    {
        $this->styleRegistry = new class(new Style()) extends AbstractStyleRegistry {};
    }

    public function testRegisterStyleShouldReuseAlreadyRegisteredStyles(): void
    {
        $style = new Style(fontBold: true);

        $this->styleRegistry->registerStyle($style);
        $this->styleRegistry->registerStyle($style);
        $this->styleRegistry->registerStyle($style);
        $this->styleRegistry->registerStyle($style);
        $this->styleRegistry->registerStyle($style);

        self::assertCount(2, $this->styleRegistry->getRegisteredStyles());
    }
}
