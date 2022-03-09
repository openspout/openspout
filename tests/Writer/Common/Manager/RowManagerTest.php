<?php

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
    /**
     * @return array
     */
    public function dataProviderForTestIsEmptyRow()
    {
        return [
            // cells, expected isEmpty
            [[], true],
            [[new Cell('')], true],
            [[new Cell(''), new Cell('')], true],
            [[new Cell(''), new Cell(''), new Cell('Okay')], false],
        ];
    }

    /**
     * @dataProvider dataProviderForTestIsEmptyRow
     *
     * @param bool $expectedIsEmpty
     */
    public function testIsEmptyRow(array $cells, $expectedIsEmpty)
    {
        $rowManager = new RowManager();

        $row = new Row($cells, null);
        static::assertSame($expectedIsEmpty, $rowManager->isEmpty($row));
    }
}
