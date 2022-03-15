<?php

namespace OpenSpout\Reader\Common\Creator;

use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Common\Helper\EncodingHelper;
use OpenSpout\Common\Type;
use OpenSpout\Reader\CSV\Manager\OptionsManager as CSVOptionsManager;
use OpenSpout\Reader\CSV\Reader as CSVReader;
use OpenSpout\Reader\ODS\Manager\OptionsManager as ODSOptionsManager;
use OpenSpout\Reader\ODS\Reader as ODSReader;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Manager\OptionsManager as XLSXOptionsManager;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\CachingStrategyFactory;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\MemoryLimit;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;

/**
 * This factory is used to create readers, based on the type of the file to be read.
 * It supports CSV, XLSX and ODS formats.
 */
final class ReaderFactory
{
    /**
     * Creates a reader by file extension.
     *
     * @param string $path The path to the spreadsheet file. Supported extensions are .csv,.ods and .xlsx
     *
     * @throws \OpenSpout\Common\Exception\UnsupportedTypeException
     */
    public static function createFromFile(string $path): ReaderInterface
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return self::createFromType($extension);
    }

    /**
     * This creates an instance of the appropriate reader, given the type of the file to be read.
     *
     * @param string $readerType Type of the reader to instantiate
     *
     * @throws \OpenSpout\Common\Exception\UnsupportedTypeException
     */
    public static function createFromType(string $readerType): ReaderInterface
    {
        switch ($readerType) {
            case Type::CSV: return self::createCSVReader();

            case Type::XLSX: return self::createXLSXReader();

            case Type::ODS: return self::createODSReader();

            default:
                throw new UnsupportedTypeException('No readers supporting the given type: '.$readerType);
        }
    }

    private static function createCSVReader(): CSVReader
    {
        return new CSVReader(new CSVOptionsManager(), EncodingHelper::factory());
    }

    private static function createXLSXReader(): XLSXReader
    {
        $optionsManager = new XLSXOptionsManager();

        return new XLSXReader($optionsManager, new CachingStrategyFactory(new MemoryLimit(ini_get('memory_limit'))));
    }

    private static function createODSReader(): ODSReader
    {
        $optionsManager = new ODSOptionsManager();

        return new ODSReader($optionsManager);
    }
}
