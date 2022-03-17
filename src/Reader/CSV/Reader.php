<?php

namespace OpenSpout\Reader\CSV;

use OpenSpout\Common\Helper\EncodingHelper;
use OpenSpout\Reader\Common\Entity\Options;
use OpenSpout\Reader\CSV\Manager\OptionsManager;
use OpenSpout\Reader\ReaderAbstract;

/**
 * @extends ReaderAbstract<SheetIterator>
 */
final class Reader extends ReaderAbstract
{
    /** @var resource Pointer to the file to be written */
    private $filePointer;

    /** @var SheetIterator To iterator over the CSV unique "sheet" */
    private SheetIterator $sheetIterator;

    /** @var string Original value for the "auto_detect_line_endings" INI value */
    private string $originalAutoDetectLineEndings;

    /** @var bool Whether the code is running with PHP >= 8.1 */
    private bool $isRunningAtLeastPhp81;

    private EncodingHelper $encodingHelper;

    public function __construct(
        OptionsManager $optionsManager,
        EncodingHelper $encodingHelper
    ) {
        parent::__construct($optionsManager);
        $this->isRunningAtLeastPhp81 = version_compare(PHP_VERSION, '8.1.0') >= 0;
        $this->encodingHelper = $encodingHelper;
    }

    public static function factory(): self
    {
        return new self(new OptionsManager(), EncodingHelper::factory());
    }

    /**
     * Sets the field delimiter for the CSV.
     * Needs to be called before opening the reader.
     *
     * @param string $fieldDelimiter Character that delimits fields
     */
    public function setFieldDelimiter(string $fieldDelimiter): self
    {
        $this->optionsManager->setOption(Options::FIELD_DELIMITER, $fieldDelimiter);

        return $this;
    }

    /**
     * Sets the field enclosure for the CSV.
     * Needs to be called before opening the reader.
     *
     * @param string $fieldEnclosure Character that enclose fields
     */
    public function setFieldEnclosure(string $fieldEnclosure): self
    {
        $this->optionsManager->setOption(Options::FIELD_ENCLOSURE, $fieldEnclosure);

        return $this;
    }

    /**
     * Sets the encoding of the CSV file to be read.
     * Needs to be called before opening the reader.
     *
     * @param string $encoding Encoding of the CSV file to be read
     */
    public function setEncoding(string $encoding): self
    {
        $this->optionsManager->setOption(Options::ENCODING, $encoding);

        return $this;
    }

    /**
     * Returns whether stream wrappers are supported.
     */
    protected function doesSupportStreamWrapper(): bool
    {
        return true;
    }

    /**
     * Opens the file at the given path to make it ready to be read.
     * If setEncoding() was not called, it assumes that the file is encoded in UTF-8.
     *
     * @param string $filePath Path of the CSV file to be read
     *
     * @throws \OpenSpout\Common\Exception\IOException
     */
    protected function openReader(string $filePath): void
    {
        // "auto_detect_line_endings" is deprecated in PHP 8.1
        if (!$this->isRunningAtLeastPhp81) {
            $originalAutoDetectLineEndings = \ini_get('auto_detect_line_endings');
            \assert(false !== $originalAutoDetectLineEndings);
            $this->originalAutoDetectLineEndings = $originalAutoDetectLineEndings;
            ini_set('auto_detect_line_endings', '1');
        }

        $resource = fopen($filePath, 'r');
        \assert(false !== $resource);
        $this->filePointer = $resource;

        $this->sheetIterator = new SheetIterator(
            new Sheet(
                new RowIterator(
                    $this->filePointer,
                    $this->optionsManager,
                    $this->encodingHelper
                )
            )
        );
    }

    /**
     * Returns an iterator to iterate over sheets.
     *
     * @return SheetIterator To iterate over sheets
     */
    protected function getConcreteSheetIterator(): SheetIterator
    {
        return $this->sheetIterator;
    }

    /**
     * Closes the reader. To be used after reading the file.
     */
    protected function closeReader(): void
    {
        fclose($this->filePointer);

        // "auto_detect_line_endings" is deprecated in PHP 8.1
        if (!$this->isRunningAtLeastPhp81) {
            ini_set('auto_detect_line_endings', $this->originalAutoDetectLineEndings);
        }
    }
}
