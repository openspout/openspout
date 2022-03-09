<?php

namespace OpenSpout\Common\Entity\Style;

use OpenSpout\Writer\Common\Creator\Style\BorderBuilder;
use OpenSpout\Writer\Exception\Border\InvalidNameException;
use OpenSpout\Writer\Exception\Border\InvalidStyleException;
use OpenSpout\Writer\Exception\Border\InvalidWidthException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class BorderTest extends TestCase
{
    public function testValidInstance()
    {
        $noConstructorParams = new Border();
        $withConstructorParams = new Border([
            new BorderPart(Border::LEFT),
        ]);
        $this->expectNotToPerformAssertions();
    }

    public function testInvalidBorderPart()
    {
        $this->expectException(InvalidNameException::class);

        new BorderPart('invalid');
    }

    public function testInvalidBorderPartStyle()
    {
        $this->expectException(InvalidStyleException::class);

        new BorderPart(Border::LEFT, Color::BLACK, Border::WIDTH_THIN, 'invalid');
    }

    public function testInvalidBorderPartWidth()
    {
        $this->expectException(InvalidWidthException::class);

        new BorderPart(Border::LEFT, Color::BLACK, 'invalid', Border::STYLE_DASHED);
    }

    public function testNotMoreThanFourPartsPossible()
    {
        $border = new Border();
        $border
            ->addPart(new BorderPart(Border::LEFT))
            ->addPart(new BorderPart(Border::RIGHT))
            ->addPart(new BorderPart(Border::TOP))
            ->addPart(new BorderPart(Border::BOTTOM))
            ->addPart(new BorderPart(Border::LEFT))
        ;

        static::assertCount(4, $border->getParts(), 'There should never be more than 4 border parts');
    }

    public function testSetParts()
    {
        $border = new Border();
        $border->setParts([
            new BorderPart(Border::LEFT),
        ]);

        static::assertCount(1, $border->getParts(), 'It should be possible to set the border parts');
    }

    public function testBorderBuilderFluent()
    {
        $border = (new BorderBuilder())
            ->setBorderBottom()
            ->setBorderTop()
            ->setBorderLeft()
            ->setBorderRight()
            ->build()
        ;
        static::assertCount(4, $border->getParts(), 'The border builder exposes a fluent interface');
    }

    /**
     * :D :S.
     */
    public function testAnyCombinationOfAllowedBorderPartsParams()
    {
        $color = Color::BLACK;
        foreach (BorderPart::getAllowedNames() as $allowedName) {
            foreach (BorderPart::getAllowedStyles() as $allowedStyle) {
                foreach (BorderPart::getAllowedWidths() as $allowedWidth) {
                    $borderPart = new BorderPart($allowedName, $color, $allowedWidth, $allowedStyle);
                    $border = new Border();
                    $border->addPart($borderPart);
                    static::assertCount(1, $border->getParts());

                    /** @var BorderPart $part */
                    $part = $border->getParts()[$allowedName];

                    static::assertSame($allowedStyle, $part->getStyle());
                    static::assertSame($allowedWidth, $part->getWidth());
                    static::assertSame($color, $part->getColor());
                }
            }
        }
    }
}
