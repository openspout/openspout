<?php

declare(strict_types=1);

namespace OpenSpout\Reader\ODS;

use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Helper\Escaper\ODS;
use OpenSpout\Reader\AbstractReader;
use OpenSpout\Reader\Exception\NoSheetsFoundException;
use OpenSpout\Reader\ODS\Helper\SettingsHelper;
use ZipArchive;

/**
 * @extends AbstractReader<SheetIterator>
 */
final class Reader extends AbstractReader
{
    private ZipArchive $zip;

    private readonly Options $options;

    /** @var SheetIterator To iterator over the ODS sheets */
    private SheetIterator $sheetIterator;

    public function __construct(?Options $options = null)
    {
        $this->options = $options ?? new Options();
    }

    public function getSheetIterator(): SheetIterator
    {
        $this->ensureStreamOpened();

        return $this->sheetIterator;
    }

    /**
     * Returns whether stream wrappers are supported.
     */
    protected function doesSupportStreamWrapper(): bool
    {
        return false;
    }

    /**
     * Opens the file at the given file path to make it ready to be read.
     *
     * @param string $filePath Path of the file to be read
     *
     * @throws IOException            If the file at the given path or its content cannot be read
     * @throws NoSheetsFoundException If there are no sheets in the file
     */
    protected function openReader(string $filePath): void
    {
        $this->zip = new ZipArchive();

        $openResult = $this->zip->open($filePath);

        if (true !== $openResult) {
            $errorMessage = match ($openResult) {
                ZipArchive::ER_INCONS => 'Zip archive inconsistent',
                ZipArchive::ER_INVAL => 'Invalid argument',
                ZipArchive::ER_MEMORY => 'Malloc failure',
                ZipArchive::ER_NOENT => 'No such file',
                ZipArchive::ER_NOZIP => 'Not a zip archive',
                ZipArchive::ER_OPEN => 'Can\'t open file',
                ZipArchive::ER_READ => 'Read error',
                ZipArchive::ER_SEEK => 'Seek error',
                default => 'Unknown error',
            };

            throw new IOException("Could not open {$filePath} for reading: {$errorMessage}.");
        }

        $this->sheetIterator = new SheetIterator($filePath, $this->options, new ODS(), new SettingsHelper());
    }

    /**
     * Closes the reader. To be used after reading the file.
     */
    protected function closeReader(): void
    {
        $this->zip->close();
    }
}
