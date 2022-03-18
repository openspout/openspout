<?php

declare(strict_types=1);

namespace Spout\Writer\Common\Manager;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\Common\Manager\RowManager;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RowManagerTest extends TestCase
{
    public function dataProviderForTestIsEmptyRow(): array
    {
        return [
            // cells, expected isEmpty
            [[], true],
            [[Cell::fromValue('')], true],
            [[Cell::fromValue(''), Cell::fromValue('')], true],
            [[Cell::fromValue(''), Cell::fromValue(''), Cell::fromValue('Okay')], false],
        ];
    }

    /**
     * @dataProvider dataProviderForTestIsEmptyRow
     *
     * @param Cell[] $cells
     */
    public function testIsEmptyRow(array $cells, bool $expectedIsEmpty): void
    {
        $rowManager = new RowManager();

        $row = new Row($cells, null);
        self::assertSame($expectedIsEmpty, $rowManager->isEmpty($row));
    }
}
