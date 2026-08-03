<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common\Manager;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Writer\Common\Entity\Worksheet;

/**
 * @internal
 */
interface WorksheetManagerInterface
{
    /**
     * Adds a row to the worksheet.
     *
     * @param Worksheet $worksheet The worksheet to add the row to
     * @param Row       $row       The row to be added
     *
     * @throws IOException              If the data cannot be written
     * @throws InvalidArgumentException If a cell value's type is not supported
     */
    public function addRow(Worksheet $worksheet, Row $row): void;

    /**
     * Prepares the worksheet to accept data.
     *
     * @param Worksheet $worksheet The worksheet to start
     *
     * @throws IOException If the sheet data file cannot be opened for writing
     */
    public function startSheet(Worksheet $worksheet): void;

    /**
     * Closes the worksheet.
     */
    public function close(Worksheet $worksheet): void;

    /**
     * Suspends the worksheet's file handles without writing footers.
     * Used when switching away from a sheet to prevent file handle accumulation.
     * Counterpart to resumeSheet().
     */
    public function suspendSheet(Worksheet $worksheet): void;

    /**
     * Reopens the worksheet's file handles in append mode.
     * Used when switching back to a previously closed sheet.
     */
    public function resumeSheet(Worksheet $worksheet): void;
}
