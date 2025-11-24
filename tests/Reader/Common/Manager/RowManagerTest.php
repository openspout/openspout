<?php

declare(strict_types=1);

namespace OpenSpout\Reader\Common\Manager;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RowManagerTest extends TestCase
{
    /**
     * @param Cell[] $rowCells
     * @param Cell[] $expectedFilledCells
     */
    #[DataProvider('provideFillMissingIndexesWithEmptyCellsCases')]
    public function testFillMissingIndexesWithEmptyCells(array $rowCells, array $expectedFilledCells): void
    {
        $rowManager = new RowManager();

        $rowToFill = new Row($rowCells);

        $rowFilled = $rowManager->fillMissingIndexesWithEmptyCells($rowToFill);

        self::assertEquals($expectedFilledCells, $rowFilled->cells);
    }

    public static function provideFillMissingIndexesWithEmptyCellsCases(): iterable
    {
        $cell1 = Cell::fromValue(1);
        $cell3 = Cell::fromValue(3);

        return [
            [[], []],
            [[1 => $cell1, 3 => $cell3], [Cell::fromValue(''), $cell1, Cell::fromValue(''), $cell3]],
        ];
    }
}
