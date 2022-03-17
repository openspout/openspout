<?php

namespace OpenSpout\Writer\CSV;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Helper\EncodingHelper;
use OpenSpout\Writer\Common\Entity\Options;
use OpenSpout\Writer\CSV\Manager\OptionsManager;
use OpenSpout\Writer\WriterAbstract;

/**
 * This class provides support to write data to CSV files.
 */
final class Writer extends WriterAbstract
{
    /** Number of rows to write before flushing */
    public const FLUSH_THRESHOLD = 500;

    /** @var string Content-Type value for the header */
    protected static string $headerContentType = 'text/csv; charset=UTF-8';

    private int $lastWrittenRowIndex = 0;

    public function __construct(OptionsManager $optionsManager)
    {
        parent::__construct($optionsManager);
    }

    public static function factory(): self
    {
        return new self(new OptionsManager());
    }

    /**
     * Sets the field delimiter for the CSV.
     *
     * @param string $fieldDelimiter Character that delimits fields
     */
    public function setFieldDelimiter(string $fieldDelimiter): void
    {
        $this->optionsManager->setOption(Options::FIELD_DELIMITER, $fieldDelimiter);
    }

    /**
     * Sets the field enclosure for the CSV.
     *
     * @param string $fieldEnclosure Character that enclose fields
     */
    public function setFieldEnclosure(string $fieldEnclosure): void
    {
        $this->optionsManager->setOption(Options::FIELD_ENCLOSURE, $fieldEnclosure);
    }

    /**
     * Set if a BOM has to be added to the file.
     */
    public function setShouldAddBOM(bool $shouldAddBOM): void
    {
        $this->optionsManager->setOption(Options::SHOULD_ADD_BOM, $shouldAddBOM);
    }

    /**
     * Opens the CSV streamer and makes it ready to accept data.
     */
    protected function openWriter(): void
    {
        if ((bool) $this->optionsManager->getOption(Options::SHOULD_ADD_BOM)) {
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
        $fieldDelimiter = $this->optionsManager->getOption(Options::FIELD_DELIMITER);
        $fieldEnclosure = $this->optionsManager->getOption(Options::FIELD_ENCLOSURE);

        $cells = array_map(static function (Cell $value): string {
            return (string) $value->getValue();
        }, $row->getCells());
        $wasWriteSuccessful = fputcsv($this->filePointer, $cells, $fieldDelimiter, $fieldEnclosure, '');
        if (false === $wasWriteSuccessful) {
            throw new IOException('Unable to write data');
        }

        ++$this->lastWrittenRowIndex;
        if (0 === $this->lastWrittenRowIndex % self::FLUSH_THRESHOLD) {
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
