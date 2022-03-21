<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common\Manager\Style;

use AssertionError;
use OpenSpout\Common\Entity\Style\Style;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class StyleRegistryTest extends TestCase
{
    private Style $defaultStyle;

    private AbstractStyleRegistry $styleRegistry;

    protected function setUp(): void
    {
        $this->defaultStyle = (new Style());
        $this->styleRegistry = new class($this->defaultStyle) extends AbstractStyleRegistry {};
    }

    public function testSerializeShouldNotTakeIntoAccountId(): void
    {
        $style1 = (new Style())->setFontBold();
        $style1->setId(1);

        $style2 = (new Style())->setFontBold();
        $style2->setId(2);

        self::assertSame($this->styleRegistry->serialize($style1), $this->styleRegistry->serialize($style2));
    }

    public function testRegisterStyleShouldUpdateId(): void
    {
        $style1 = (new Style())->setFontBold();
        $style2 = (new Style())->setFontUnderline();

        self::assertSame(0, $this->defaultStyle->getId(), 'Default style ID should be 0');

        $registeredStyle1 = $this->styleRegistry->registerStyle($style1);
        $registeredStyle2 = $this->styleRegistry->registerStyle($style2);

        self::assertSame(1, $registeredStyle1->getId());
        self::assertSame(2, $registeredStyle2->getId());

        try {
            (new Style())->getId();
            self::fail('Style::getId should never be called before registration');
        } catch (AssertionError $assertionError) {
        }
    }

    public function testRegisterStyleShouldReuseAlreadyRegisteredStyles(): void
    {
        $style = (new Style())->setFontBold();

        $registeredStyle1 = $this->styleRegistry->registerStyle($style);
        $registeredStyle2 = $this->styleRegistry->registerStyle($style);

        self::assertSame(1, $registeredStyle1->getId());
        self::assertSame(1, $registeredStyle2->getId());
    }
}
