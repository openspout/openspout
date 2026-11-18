<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX;

use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Helper\Escaper\XLSX;
use OpenSpout\Reader\AbstractReader;
use OpenSpout\Reader\Exception\NoSheetsFoundException;
use OpenSpout\Reader\Exception\ReaderException;
use OpenSpout\Reader\XLSX\Encryption\CompoundFile;
use OpenSpout\Reader\XLSX\Encryption\PackageDecryptor;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\CachingStrategyFactory;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\CachingStrategyFactoryInterface;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\MemoryLimit;
use OpenSpout\Reader\XLSX\Manager\SharedStringsManager;
use OpenSpout\Reader\XLSX\Manager\SheetManager;
use OpenSpout\Reader\XLSX\Manager\WorkbookRelationshipsManager;
use ZipArchive;

/**
 * @extends AbstractReader<SheetIterator>
 */
final class Reader extends AbstractReader
{
    private ZipArchive $zip;

    /** @var SharedStringsManager Manages shared strings */
    private SharedStringsManager $sharedStringsManager;

    /** @var SheetIterator To iterator over the XLSX sheets */
    private SheetIterator $sheetIterator;

    private readonly Options $options;
    private readonly CachingStrategyFactoryInterface $cachingStrategyFactory;

    /** @var null|string Path of the decrypted copy of the file being read, when it is password-protected */
    private ?string $decryptedFilePath = null;

    public function __construct(
        ?Options $options = null,
        ?CachingStrategyFactoryInterface $cachingStrategyFactory = null
    ) {
        $this->options = $options ?? new Options();

        if (null === $cachingStrategyFactory) {
            $memoryLimit = \ini_get('memory_limit');
            $cachingStrategyFactory = new CachingStrategyFactory(new MemoryLimit($memoryLimit));
        }
        $this->cachingStrategyFactory = $cachingStrategyFactory;
    }

    public function __destruct()
    {
        // A password-protected file must not stay decrypted on disk, even if the reader is never closed
        $this->deleteDecryptedFile();
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
     * It also parses the sharedStrings.xml file to get all the shared strings available in memory
     * and fetches all the available sheets.
     *
     * @param string $filePath Path of the file to be read
     *
     * @throws IOException            If the file at the given path or its content cannot be read
     * @throws NoSheetsFoundException If there are no sheets in the file
     * @throws ReaderException        If the file is password-protected and cannot be decrypted
     */
    protected function openReader(string $filePath): void
    {
        if (CompoundFile::isCompoundFile($filePath)) {
            // Password-protected files are not ZIP files, but containers of an encrypted ZIP file
            $this->decryptedFilePath = (new PackageDecryptor())->decrypt($filePath, $this->options->password, $this->options->tempFolder);
        }

        try {
            $this->openZip($filePath, $this->decryptedFilePath ?? $filePath);
        } catch (IOException|ReaderException $exception) {
            $this->deleteDecryptedFile();

            throw $exception;
        }
    }

    /**
     * Closes the reader. To be used after reading the file.
     */
    protected function closeReader(): void
    {
        $this->zip->close();
        $this->sharedStringsManager->cleanup();
        $this->deleteDecryptedFile();
    }

    /**
     * @param string $filePath    Path of the file given by the user
     * @param string $zipFilePath Path of the ZIP file to read, which differs from $filePath for password-protected files
     */
    private function openZip(string $filePath, string $zipFilePath): void
    {
        $this->zip = new ZipArchive();

        $openResult = $this->zip->open($zipFilePath);

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

        $this->sharedStringsManager = new SharedStringsManager(
            $zipFilePath,
            $this->options,
            new WorkbookRelationshipsManager($zipFilePath),
            $this->cachingStrategyFactory
        );

        if ($this->sharedStringsManager->hasSharedStrings()) {
            // Extracts all the strings from the sheets for easy access in the future
            $this->sharedStringsManager->extractSharedStrings();
        }

        $this->sheetIterator = new SheetIterator(
            new SheetManager(
                $zipFilePath,
                $this->options,
                $this->sharedStringsManager,
                new XLSX()
            )
        );
    }

    private function deleteDecryptedFile(): void
    {
        // The path is kept when the file could not be deleted, so that it gets tried again later
        if (null !== $this->decryptedFilePath && (!file_exists($this->decryptedFilePath) || unlink($this->decryptedFilePath))) {
            $this->decryptedFilePath = null;
        }
    }
}
