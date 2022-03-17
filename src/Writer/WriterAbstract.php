<?php

namespace OpenSpout\Writer;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Exception\SpoutException;
use OpenSpout\Common\Helper\FileSystemHelper;
use OpenSpout\Common\Manager\OptionsManagerInterface;
use OpenSpout\Writer\Common\Entity\Options;
use OpenSpout\Writer\Exception\WriterAlreadyOpenedException;
use OpenSpout\Writer\Exception\WriterNotOpenedException;

abstract class WriterAbstract implements WriterInterface
{
    /** @var string Path to the output file */
    protected string $outputFilePath;

    /** @var resource Pointer to the file/stream we will write to */
    protected $filePointer;

    /** @var bool Indicates whether the writer has been opened or not */
    protected bool $isWriterOpened = false;

    /** @var OptionsManagerInterface Writer options manager */
    protected OptionsManagerInterface $optionsManager;

    /** @var string Content-Type value for the header - to be defined by child class */
    protected static string $headerContentType;

    public function __construct(
        OptionsManagerInterface $optionsManager
    ) {
        $this->optionsManager = $optionsManager;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultRowStyle(Style $defaultStyle): void
    {
        $this->optionsManager->setOption(Options::DEFAULT_ROW_STYLE, $defaultStyle);
    }

    /**
     * {@inheritdoc}
     */
    public function openToFile($outputFilePath): void
    {
        $this->outputFilePath = $outputFilePath;

        $resource = fopen($this->outputFilePath, 'wb+');
        if (false === $resource) {
            throw new IOException("Unable to open file {$this->outputFilePath}");
        }
        $this->filePointer = $resource;

        $this->openWriter();
        $this->isWriterOpened = true;
    }

    /**
     * @codeCoverageIgnore
     * {@inheritdoc}
     */
    public function openToBrowser($outputFileName): void
    {
        $this->outputFilePath = basename($outputFileName);

        $resource = fopen('php://output', 'w');
        \assert(false !== $resource);
        $this->filePointer = $resource;

        // Clear any previous output (otherwise the generated file will be corrupted)
        // @see https://github.com/box/spout/issues/241
        if (ob_get_length() > 0) {
            ob_end_clean();
        }

        /*
         * Set headers
         *
         * For newer browsers such as Firefox, Chrome, Opera, Safari, etc., they all support and use `filename*`
         * specified by the new standard, even if they do not automatically decode filename; it does not matter;
         * and for older versions of Internet Explorer, they are not recognized `filename*`, will automatically
         * ignore it and use the old `filename` (the only minor flaw is that there must be an English suffix name).
         * In this way, the multi-browser multi-language compatibility problem is perfectly solved, which does not
         * require UA judgment and is more in line with the standard.
         *
         * @see https://github.com/box/spout/issues/745
         * @see https://tools.ietf.org/html/rfc6266
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Disposition
         */
        header('Content-Type: '.static::$headerContentType);
        header(
            'Content-Disposition: attachment; '.
            'filename="'.rawurldecode($this->outputFilePath).'"; '.
            'filename*=UTF-8\'\''.rawurldecode($this->outputFilePath)
        );

        /*
         * When forcing the download of a file over SSL,IE8 and lower browsers fail
         * if the Cache-Control and Pragma headers are not set.
         *
         * @see http://support.microsoft.com/KB/323308
         * @see https://github.com/liuggio/ExcelBundle/issues/45
         */
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        $this->openWriter();
        $this->isWriterOpened = true;
    }

    /**
     * {@inheritdoc}
     */
    public function addRow(Row $row): void
    {
        if (!$this->isWriterOpened) {
            throw new WriterNotOpenedException('The writer needs to be opened before adding row.');
        }

        try {
            $this->addRowToWriter($row);
        } catch (SpoutException $e) {
            // if an exception occurs while writing data,
            // close the writer and remove all files created so far.
            $this->closeAndAttemptToCleanupAllFiles();

            // re-throw the exception to alert developers of the error
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addRows(array $rows): void
    {
        foreach ($rows as $row) {
            if (!$row instanceof Row) {
                $this->closeAndAttemptToCleanupAllFiles();

                throw new InvalidArgumentException('The input should be an array of Row');
            }

            $this->addRow($row);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        if (!$this->isWriterOpened) {
            return;
        }

        $this->closeWriter();

        if (\is_resource($this->filePointer)) {
            fclose($this->filePointer);
        }

        $this->isWriterOpened = false;
    }

    /**
     * Opens the streamer and makes it ready to accept data.
     *
     * @throws IOException If the writer cannot be opened
     */
    abstract protected function openWriter(): void;

    /**
     * Adds a row to the currently opened writer.
     *
     * @param Row $row The row containing cells and styles
     *
     * @throws WriterNotOpenedException If the workbook is not created yet
     * @throws IOException              If unable to write data
     */
    abstract protected function addRowToWriter(Row $row): void;

    /**
     * Closes the streamer, preventing any additional writing.
     */
    abstract protected function closeWriter(): void;

    /**
     * Checks if the writer has already been opened, since some actions must be done before it gets opened.
     * Throws an exception if already opened.
     *
     * @param string $message Error message
     *
     * @throws WriterAlreadyOpenedException if the writer was already opened and must not be
     */
    protected function throwIfWriterAlreadyOpened(string $message): void
    {
        if ($this->isWriterOpened) {
            throw new WriterAlreadyOpenedException($message);
        }
    }

    /**
     * Closes the writer and attempts to cleanup all files that were
     * created during the writing process (temp files & final file).
     */
    private function closeAndAttemptToCleanupAllFiles(): void
    {
        // close the writer, which should remove all temp files
        $this->close();

        // remove output file if it was created
        if (file_exists($this->outputFilePath)) {
            $outputFolderPath = \dirname($this->outputFilePath);
            $fileSystemHelper = new FileSystemHelper($outputFolderPath);
            $fileSystemHelper->deleteFile($this->outputFilePath);
        }
    }
}
