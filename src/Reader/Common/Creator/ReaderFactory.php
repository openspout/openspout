<?php

declare(strict_types=1);

namespace OpenSpout\Reader\Common\Creator;

use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Reader\CSV\Reader as CSVReader;
use OpenSpout\Reader\ODS\Reader as ODSReader;
use OpenSpout\Reader\ReaderInterface;
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

        return match ($extension) {
            'csv' => new CSVReader(),
            'xlsx' => new XLSXReader(),
            'ods' => new ODSReader(),
            default => throw new UnsupportedTypeException('No readers supporting the given type: '.$extension),
        };
    }
}
