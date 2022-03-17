<?php

namespace OpenSpout\Writer\Common\Creator;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;

/**
 * Factory to create external entities.
 */
final class WriterEntityFactory
{
    /**
     * @param mixed[] $cellValues
     */
    public static function createRowFromArray(array $cellValues = [], Style $rowStyle = null): Row
    {
        $cells = array_map(static function (mixed $cellValue): Cell {
            return Cell::fromValue($cellValue);
        }, $cellValues);

        return new Row($cells, $rowStyle);
    }
}
