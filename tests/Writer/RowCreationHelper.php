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
     * @return Row
     */
    protected function createRowFromValues(array $cellValues)
    {
        return $this->createStyledRowFromValues($cellValues, null);
    }

    /**
     * @return Row
     */
    protected function createStyledRowFromValues(array $cellValues, Style $rowStyle = null)
    {
        return WriterEntityFactory::createRowFromArray($cellValues, $rowStyle);
    }

    /**
     * @return Row[]
     */
    protected function createRowsFromValues(array $rowValues)
    {
        return $this->createStyledRowsFromValues($rowValues, null);
    }

    /**
     * @return Row[]
     */
    protected function createStyledRowsFromValues(array $rowValues, Style $rowsStyle = null)
    {
        $rows = [];

        foreach ($rowValues as $cellValues) {
            $rows[] = $this->createStyledRowFromValues($cellValues, $rowsStyle);
        }

        return $rows;
    }
}
