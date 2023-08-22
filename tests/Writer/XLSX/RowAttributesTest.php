<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

use OpenSpout\Common\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RowAttributesTest extends TestCase
{
    public function testSetOutlineLevel(): void
    {
        $attributes = new RowAttributes(1);

        self::assertEquals(0, $attributes->setOutlineLevel(0)->getOutlineLevel());
        self::assertEquals(7, $attributes->setOutlineLevel(7)->getOutlineLevel());
        self::assertNull($attributes->setOutlineLevel(null)->getOutlineLevel());
    }

    public function testSetOutlineLevelLowerThanZero(): void
    {
        $attributes = new RowAttributes();

        self::expectException(InvalidArgumentException::class);

        $attributes->setOutlineLevel(-1);
    }

    public function testSetOutlineLevelGreaterThanSeven(): void
    {
        $attributes = new RowAttributes();

        self::expectException(InvalidArgumentException::class);

        $attributes->setOutlineLevel(8);
    }

    public function testIsEmpty(): void
    {
        $attributes = new RowAttributes();

        self::assertTrue($attributes->isEmpty());
        self::assertFalse((clone $attributes)->setOutlineLevel(1)->isEmpty());
        self::assertFalse((clone $attributes)->setCollapsed(true)->isEmpty());
        self::assertFalse((clone $attributes)->setVisible(false)->isEmpty());
    }
}
