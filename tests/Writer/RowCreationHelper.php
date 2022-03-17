<?php

namespace OpenSpout\Writer;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\Common\Creator\WriterEntityFactory;

/**
 * Trait RowCreationHelper.
 */
trait RowCreationHelper
{
    /**
     * @param mixed[] $cellValues
     */
    private function createRowFromValues(array $cellValues): Row
    {
        return $this->createStyledRowFromValues($cellValues, null);
    }

    /**
     * @param mixed[] $cellValues
     */
    private function createStyledRowFromValues(array $cellValues, ?Style $rowStyle): Row
    {
        return WriterEntityFactory::createRowFromArray($cellValues, $rowStyle);
    }

    /**
     * @param mixed[][] $rowValues
     *
     * @return Row[]
     */
    private function createRowsFromValues(array $rowValues): array
    {
        return $this->createStyledRowsFromValues($rowValues, null);
    }

    /**
     * @param mixed[][] $rowValues
     *
     * @return Row[]
     */
    private function createStyledRowsFromValues(array $rowValues, ?Style $rowsStyle): array
    {
        $rows = [];

        foreach ($rowValues as $cellValues) {
            $rows[] = $this->createStyledRowFromValues($cellValues, $rowsStyle);
        }

        return $rows;
    }
}
