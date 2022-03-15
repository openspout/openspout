<?php

namespace OpenSpout\Writer\Common\Creator;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Common\Type;
use OpenSpout\Writer\WriterInterface;

/**
 * Factory to create external entities.
 */
class WriterEntityFactory
{
    /**
     * This creates an instance of the appropriate writer, given the type of the file to be written.
     *
     * @param string $writerType Type of the writer to instantiate
     *
     * @throws \OpenSpout\Common\Exception\UnsupportedTypeException
     */
    public static function createWriter(string $writerType): WriterInterface
    {
        return WriterFactory::createFromType($writerType);
    }

    /**
     * This creates an instance of the appropriate writer, given the extension of the file to be written.
     *
     * @param string $path The path to the spreadsheet file. Supported extensions are .csv, .ods and .xlsx
     *
     * @throws \OpenSpout\Common\Exception\UnsupportedTypeException
     */
    public static function createWriterFromFile(string $path): WriterInterface
    {
        return WriterFactory::createFromFile($path);
    }

    /**
     * This creates an instance of a CSV writer.
     */
    public static function createCSVWriter(): \OpenSpout\Writer\CSV\Writer
    {
        try {
            return WriterFactory::createFromType(Type::CSV);
        } catch (UnsupportedTypeException $e) {
            // should never happen
            return null;
        }
    }

    /**
     * This creates an instance of a XLSX writer.
     */
    public static function createXLSXWriter(): \OpenSpout\Writer\XLSX\Writer
    {
        try {
            return WriterFactory::createFromType(Type::XLSX);
        } catch (UnsupportedTypeException $e) {
            // should never happen
            return null;
        }
    }

    /**
     * This creates an instance of a ODS writer.
     */
    public static function createODSWriter(): \OpenSpout\Writer\ODS\Writer
    {
        try {
            return WriterFactory::createFromType(Type::ODS);
        } catch (UnsupportedTypeException $e) {
            // should never happen
            return null;
        }
    }

    /**
     * @param Cell[] $cells
     */
    public static function createRow(array $cells = [], Style $rowStyle = null): Row
    {
        return new Row($cells, $rowStyle);
    }

    public static function createRowFromArray(array $cellValues = [], Style $rowStyle = null): Row
    {
        $cells = array_map(function ($cellValue) {
            return new Cell($cellValue);
        }, $cellValues);

        return new Row($cells, $rowStyle);
    }

    /**
     * @param mixed $cellValue
     */
    public static function createCell($cellValue, Style $cellStyle = null): Cell
    {
        return new Cell($cellValue, $cellStyle);
    }
}
