<?php

namespace OpenSpout\Writer\Common\Manager;

use OpenSpout\Common\Entity\Cell\EmptyCell;
use OpenSpout\Common\Entity\Row;

final class RowManager
{
    /**
     * Detect whether a row is considered empty.
     * An empty row has all of its cells empty.
     */
    public function isEmpty(Row $row): bool
    {
        foreach ($row->getCells() as $cell) {
            if (!$cell instanceof EmptyCell) {
                return false;
            }
        }

        return true;
    }
}
