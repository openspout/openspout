<?php

namespace OpenSpout\Reader\CSV\Creator;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Helper\EncodingHelper;
use OpenSpout\Common\Manager\OptionsManagerInterface;
use OpenSpout\Reader\Common\Creator\InternalEntityFactoryInterface;
use OpenSpout\Reader\CSV\RowIterator;
use OpenSpout\Reader\CSV\Sheet;
use OpenSpout\Reader\CSV\SheetIterator;

/**
 * Factory to create entities.
 */
class InternalEntityFactory implements InternalEntityFactoryInterface
{
    /**
     * @var EncodingHelper
     */
    private $encodingHelper;

    public function __construct(EncodingHelper $encodingHelper)
    {
        $this->encodingHelper = $encodingHelper;
    }

    /**
     * @param resource                $filePointer    Pointer to the CSV file to read
     * @param OptionsManagerInterface $optionsManager
     *
     * @return SheetIterator
     */
    public function createSheetIterator($filePointer, $optionsManager)
    {
        $rowIterator = $this->createRowIterator($filePointer, $optionsManager);
        $sheet = $this->createSheet($rowIterator);

        return new SheetIterator($sheet);
    }

    /**
     * @param Cell[] $cells
     *
     * @return Row
     */
    public function createRow(array $cells = [])
    {
        return new Row($cells, null);
    }

    /**
     * @param mixed $cellValue
     *
     * @return Cell
     */
    public function createCell($cellValue)
    {
        return new Cell($cellValue);
    }

    /**
     * @return Row
     */
    public function createRowFromArray(array $cellValues = [])
    {
        $cells = array_map(function ($cellValue) {
            return $this->createCell($cellValue);
        }, $cellValues);

        return $this->createRow($cells);
    }

    /**
     * @param RowIterator $rowIterator
     *
     * @return Sheet
     */
    private function createSheet($rowIterator)
    {
        return new Sheet($rowIterator);
    }

    /**
     * @param resource                $filePointer    Pointer to the CSV file to read
     * @param OptionsManagerInterface $optionsManager
     *
     * @return RowIterator
     */
    private function createRowIterator($filePointer, $optionsManager)
    {
        return new RowIterator($filePointer, $optionsManager, $this->encodingHelper, $this);
    }
}
