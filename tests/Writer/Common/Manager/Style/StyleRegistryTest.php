<?php

namespace OpenSpout\Writer\Common\Manager\Style;

use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\Common\Creator\Style\StyleBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class StyleRegistryTest extends TestCase
{
    /** @var Style */
    private $defaultStyle;

    /** @var StyleRegistry */
    private $styleRegistry;

    protected function setUp(): void
    {
        $this->defaultStyle = (new StyleBuilder())->build();
        $this->styleRegistry = new StyleRegistry($this->defaultStyle);
    }

    public function testSerializeShouldNotTakeIntoAccountId()
    {
        $style1 = (new StyleBuilder())->setFontBold()->build();
        $style1->setId(1);

        $style2 = (new StyleBuilder())->setFontBold()->build();
        $style2->setId(2);

        static::assertSame($this->styleRegistry->serialize($style1), $this->styleRegistry->serialize($style2));
    }

    public function testRegisterStyleShouldUpdateId()
    {
        $style1 = (new StyleBuilder())->setFontBold()->build();
        $style2 = (new StyleBuilder())->setFontUnderline()->build();

        static::assertSame(0, $this->defaultStyle->getId(), 'Default style ID should be 0');
        static::assertNull($style1->getId());
        static::assertNull($style2->getId());

        $registeredStyle1 = $this->styleRegistry->registerStyle($style1);
        $registeredStyle2 = $this->styleRegistry->registerStyle($style2);

        static::assertSame(1, $registeredStyle1->getId());
        static::assertSame(2, $registeredStyle2->getId());
    }

    public function testRegisterStyleShouldReuseAlreadyRegisteredStyles()
    {
        $style = (new StyleBuilder())->setFontBold()->build();

        $registeredStyle1 = $this->styleRegistry->registerStyle($style);
        $registeredStyle2 = $this->styleRegistry->registerStyle($style);

        static::assertSame(1, $registeredStyle1->getId());
        static::assertSame(1, $registeredStyle2->getId());
    }
}
