<?php

namespace OpenSpout\Writer\Common\Creator;

use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Common\Type;
use OpenSpout\Writer\CSV\Manager\OptionsManager as CSVOptionsManager;
use OpenSpout\Writer\CSV\Writer as CSVWriter;
use OpenSpout\Writer\ODS\Creator\ManagerFactory as ODSManagerFactory;
use OpenSpout\Writer\ODS\Manager\OptionsManager as ODSOptionsManager;
use OpenSpout\Writer\ODS\Writer as ODSWriter;
use OpenSpout\Writer\WriterInterface;
use OpenSpout\Writer\XLSX\Creator\ManagerFactory as XLSXManagerFactory;
use OpenSpout\Writer\XLSX\Manager\OptionsManager as XLSXOptionsManager;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;

/**
 * This factory is used to create writers, based on the type of the file to be read.
 * It supports CSV, XLSX and ODS formats.
 */
final class WriterFactory
{
    /**
     * This creates an instance of the appropriate writer, given the extension of the file to be written.
     *
     * @param string $path The path to the spreadsheet file. Supported extensions are .csv,.ods and .xlsx
     *
     * @throws \OpenSpout\Common\Exception\UnsupportedTypeException
     */
    public static function createFromFile(string $path): WriterInterface
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return self::createFromType($extension);
    }

    /**
     * This creates an instance of the appropriate writer, given the type of the file to be written.
     *
     * @param string $writerType Type of the writer to instantiate
     *
     * @throws \OpenSpout\Common\Exception\UnsupportedTypeException
     */
    public static function createFromType(string $writerType): WriterInterface
    {
        switch ($writerType) {
            case Type::CSV: return self::createCSVWriter();

            case Type::XLSX: return self::createXLSXWriter();

            case Type::ODS: return self::createODSWriter();

            default:
                throw new UnsupportedTypeException('No writers supporting the given type: '.$writerType);
        }
    }

    private static function createCSVWriter(): CSVWriter
    {
        $optionsManager = new CSVOptionsManager();

        return new CSVWriter($optionsManager);
    }

    private static function createXLSXWriter(): XLSXWriter
    {
        $optionsManager = new XLSXOptionsManager();
        $managerFactory = new XLSXManagerFactory();

        return new XLSXWriter($optionsManager, $managerFactory);
    }

    private static function createODSWriter(): ODSWriter
    {
        $optionsManager = new ODSOptionsManager();
        $managerFactory = new ODSManagerFactory();

        return new ODSWriter($optionsManager, $managerFactory);
    }
}
