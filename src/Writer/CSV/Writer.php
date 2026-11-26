<?php

declare(strict_types=1);

namespace OpenSpout\Writer\CSV;

use Exception;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Common\Helper\EncodingHelper;
use OpenSpout\Writer\AbstractWriter;

final class Writer extends AbstractWriter
{
    /** @var string Content-Type value for the header */
    protected static string $headerContentType = 'text/csv; charset=UTF-8';

    private readonly Options $options;

    private int $lastWrittenRowIndex = 0;

    public function __construct(?Options $options = null)
    {
        $this->options = $options ?? new Options();
    }

    public function getOptions(): Options
    {
        return $this->options;
    }

    public function setCreator(string $creator): void
    {
        throw new Exception('Method unsopported for CSV documents');
    }

    /**
     * Opens the CSV streamer and makes it ready to accept data.
     */
    protected function openWriter(): void
    {
        if ($this->options->SHOULD_ADD_BOM) {
            // Adds UTF-8 BOM for Unicode compatibility
            fwrite($this->filePointer, EncodingHelper::BOM_UTF8);
        }
    }

    /**
     * Adds a row to the currently opened writer.
     *
     * @param Row $row The row containing cells and styles
     *
     * @throws IOException If unable to write data
     */
    protected function addRowToWriter(Row $row): void
    {
        $cells = [];
        foreach ($row->cells as $cell) {
            if ($cell instanceof Cell\StringCell || $cell instanceof Cell\EmptyCell || $cell instanceof Cell\NumericCell || $cell instanceof Cell\FormulaCell) {
                $cells[] = $cell->getValue();
            } elseif ($cell instanceof Cell\BooleanCell) {
                $cells[] = $cell->getValue() ? '1' : '0';
            } elseif ($cell instanceof Cell\DateTimeCell) {
                $cells[] = $cell->getValue()->format(DATE_ATOM);
            } elseif ($cell instanceof Cell\DateIntervalCell) {
                $cells[] = $cell->getValue()->format('P%yY%mM%dDT%hH%iM%sS%fF');
            } else {
                throw new UnsupportedTypeException(\sprintf('%s is not supported in CSV format.', $cell::class));
            }
        }

        $wasWriteSuccessful = fputcsv(
            $this->filePointer,
            $cells,
            $this->options->FIELD_DELIMITER,
            $this->options->FIELD_ENCLOSURE,
            '',
            $this->options->EOL,
        );
        if (false === $wasWriteSuccessful) {
            throw new IOException('Unable to write data'); // @codeCoverageIgnore
        }

        ++$this->lastWrittenRowIndex;
        if (0 === $this->lastWrittenRowIndex % $this->options->FLUSH_THRESHOLD) {
            fflush($this->filePointer);
        }
    }

    /**
     * Closes the CSV streamer, preventing any additional writing.
     * If set, sets the headers and redirects output to the browser.
     */
    protected function closeWriter(): void
    {
        $this->lastWrittenRowIndex = 0;
    }
}
