<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common\Creator;

use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Writer\CSV\Options as CSVWriterOptions;
use OpenSpout\Writer\CSV\Writer as CSVWriter;
use OpenSpout\Writer\ODS\Options as ODSWriterOptions;
use OpenSpout\Writer\ODS\Writer as ODSWriter;
use OpenSpout\Writer\WriterInterface;
use OpenSpout\Writer\XLSX\Options as XLSXWriterOptions;
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
     * @param string $path                  The path to the spreadsheet file. Supported extensions are .csv,.ods and .xlsx
     * @param array<string, mixed> $options Array of options
     *
     * @throws \OpenSpout\Common\Exception\UnsupportedTypeException
     */
    public static function createFromFile(string $path, array $options = []): WriterInterface
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv' => new CSVWriter(CSVWriterOptions::fromArray($options)),
            'xlsx' => new XLSXWriter(XLSXWriterOptions::fromArray($options)),
            'ods' => new ODSWriter(ODSWriterOptions::fromArray($options)),
            default => throw new UnsupportedTypeException('No writers supporting the given type: '.$extension),
        };
    }
}
