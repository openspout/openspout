<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

use OpenSpout\Common\Entity\Row;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class OptionsTest extends TestCase
{
    public function testSetRowAttributes(): void
    {
        $attributes = new RowAttributes(2);
        $options = new Options();
        $row = Row::fromValues();

        self::assertNull($options->getOutlineMaxLevel());
        self::assertSame($row, $options->setRowAttributes($row, $attributes));
        self::assertEquals(2, $options->getOutlineMaxLevel());

        // Level cannot be decreased
        $options->setRowAttributes($row, new RowAttributes(1));

        self::assertEquals(2, $options->getOutlineMaxLevel());
    }
}
