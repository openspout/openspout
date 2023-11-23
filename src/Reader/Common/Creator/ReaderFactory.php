<?php

declare(strict_types=1);

namespace OpenSpout\Reader\Common\Creator;

use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Reader\CSV\Options as CSVReaderOptions;
use OpenSpout\Reader\CSV\Reader as CSVReader;
use OpenSpout\Reader\ODS\Options as ODSReaderOptions;
use OpenSpout\Reader\ODS\Reader as ODSReader;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Options as XLSXReaderOptions;
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
     * @param string               $path    The path to the spreadsheet file. Supported extensions are .csv,.ods and .xlsx
     * @param array<string, mixed> $options Array of options
     *
     * @throws \OpenSpout\Common\Exception\UnsupportedTypeException
     */
    public static function createFromFile(string $path, array $options = []): ReaderInterface
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv' => new CSVReader(CSVReaderOptions::fromArray($options)),
            'xlsx' => new XLSXReader(XLSXReaderOptions::fromArray($options)),
            'ods' => new ODSReader(ODSReaderOptions::fromArray($options)),
            default => throw new UnsupportedTypeException('No readers supporting the given type: '.$extension),
        };
    }

    /**
     * Creates a reader by mime type.
     *
     * @param string               $path    the path to the spreadsheet file
     * @param array<string, mixed> $options Array of options
     *
     * @throws \OpenSpout\Common\Exception\UnsupportedTypeException
     * @throws \OpenSpout\Common\Exception\IOException
     */
    public static function createFromFileByMimeType(string $path, array $options = []): ReaderInterface
    {
        if (!file_exists($path)) {
            throw new IOException("Could not open {$path} for reading! File does not exist.");
        }

        $mime_type = mime_content_type($path);

        return match ($mime_type) {
            'application/csv', 'text/csv', 'text/plain' => new CSVReader(CSVReaderOptions::fromArray($options)),
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => new XLSXReader(XLSXReaderOptions::fromArray($options)),
            'application/vnd.oasis.opendocument.spreadsheet' => new ODSReader(ODSReaderOptions::fromArray($options)),
            default => throw new UnsupportedTypeException('No readers supporting the given type: '.$mime_type),
        };
    }
}
