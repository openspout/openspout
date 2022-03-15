<?php

namespace OpenSpout\Reader\Common\Creator;

use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Common\Type;
use OpenSpout\Reader\ReaderInterface;

/**
 * Factory to create external entities.
 */
class ReaderEntityFactory
{
    /**
     * Creates a reader by file extension.
     *
     * @param string $path The path to the spreadsheet file. Supported extensions are .csv, .ods and .xlsx
     *
     * @throws \OpenSpout\Common\Exception\UnsupportedTypeException
     */
    public static function createReaderFromFile(string $path): ReaderInterface
    {
        return ReaderFactory::createFromFile($path);
    }

    /**
     * This creates an instance of a CSV reader.
     */
    public static function createCSVReader(): \OpenSpout\Reader\CSV\Reader
    {
        try {
            return ReaderFactory::createFromType(Type::CSV);
        } catch (UnsupportedTypeException $e) {
            // should never happen
            return null;
        }
    }

    /**
     * This creates an instance of a XLSX reader.
     */
    public static function createXLSXReader(): \OpenSpout\Reader\XLSX\Reader
    {
        try {
            return ReaderFactory::createFromType(Type::XLSX);
        } catch (UnsupportedTypeException $e) {
            // should never happen
            return null;
        }
    }

    /**
     * This creates an instance of a ODS reader.
     */
    public static function createODSReader(): \OpenSpout\Reader\ODS\Reader
    {
        try {
            return ReaderFactory::createFromType(Type::ODS);
        } catch (UnsupportedTypeException $e) {
            // should never happen
            return null;
        }
    }
}
