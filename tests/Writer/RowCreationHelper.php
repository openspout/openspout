<?php

declare(strict_types=1);

namespace OpenSpout\Writer;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;

/**
 * Trait RowCreationHelper.
 */
trait RowCreationHelper
{
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
            $rows[] = Row::fromValues($cellValues, $rowsStyle);
        }

        return $rows;
    }
}
