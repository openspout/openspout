<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Style;

use OpenSpout\Writer\Exception\Border\InvalidNameException;
use OpenSpout\Writer\Exception\Border\InvalidStyleException;
use OpenSpout\Writer\Exception\Border\InvalidWidthException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class BorderTest extends TestCase
{
    public function testInvalidBorderPart(): void
    {
        $this->expectException(InvalidNameException::class);

        new BorderPart(uniqid('invalid'));
    }

    public function testInvalidBorderPartStyle(): void
    {
        $this->expectException(InvalidStyleException::class);

        new BorderPart(Border::LEFT, Color::BLACK, Border::WIDTH_THIN, uniqid('invalid'));
    }

    public function testInvalidBorderPartWidth(): void
    {
        $this->expectException(InvalidWidthException::class);

        new BorderPart(Border::LEFT, Color::BLACK, uniqid('invalid'), Border::STYLE_DASHED);
    }

    public function testNotMoreThanFourPartsPossible(): void
    {
        $border = new Border(
            new BorderPart(Border::LEFT),
            new BorderPart(Border::RIGHT),
            new BorderPart(Border::TOP),
            new BorderPart(Border::BOTTOM),
            new BorderPart(Border::LEFT),
        );

        self::assertCount(4, $border->getParts(), 'There should never be more than 4 border parts');
    }

    /**
     * :D :S.
     */
    public function testAnyCombinationOfAllowedBorderPartsParams(): void
    {
        $color = Color::BLACK;
        foreach (BorderPart::allowedNames as $allowedName) {
            foreach (BorderPart::allowedStyles as $allowedStyle) {
                foreach (BorderPart::allowedWidths as $allowedWidth) {
                    $border = new Border(new BorderPart($allowedName, $color, $allowedWidth, $allowedStyle));
                    self::assertCount(1, $border->getParts());

                    $part = $border->getParts()[$allowedName];

                    self::assertSame($allowedStyle, $part->getStyle());
                    self::assertSame($allowedWidth, $part->getWidth());
                    self::assertSame($color, $part->getColor());
                }
            }
        }
    }
}
