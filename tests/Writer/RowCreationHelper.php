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
    protected function createRowFromValues(array $cellValues): Row
    {
        return $this->createStyledRowFromValues($cellValues, null);
    }

    protected function createStyledRowFromValues(array $cellValues, Style $rowStyle = null): Row
    {
        return WriterEntityFactory::createRowFromArray($cellValues, $rowStyle);
    }

    /**
     * @return Row[]
     */
    protected function createRowsFromValues(array $rowValues): array
    {
        return $this->createStyledRowsFromValues($rowValues, null);
    }

    /**
     * @return Row[]
     */
    protected function createStyledRowsFromValues(array $rowValues, Style $rowsStyle = null): array
    {
        $rows = [];

        foreach ($rowValues as $cellValues) {
            $rows[] = $this->createStyledRowFromValues($cellValues, $rowsStyle);
        }

        return $rows;
    }
}
