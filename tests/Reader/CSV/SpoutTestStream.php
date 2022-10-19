<?php

declare(strict_types=1);

namespace OpenSpout\Reader\CSV;

/**
 * Custom stream that reads CSV files located in the tests/resources/csv folder.
 * For example: spout://foobar will point to tests/resources/csv/foobar.csv.
 */
final class SpoutTestStream
{
    public const CLASS_NAME = __CLASS__;

    public const PATH_TO_CSV_RESOURCES = 'tests/resources/csv/';
    public const CSV_EXTENSION = '.csv';

    /** @var null|resource */
    public $context;

    private int $position;

    /** @var resource */
    private $fileHandle;

    public function url_stat(string $path, int $flag): array
    {
        $filePath = $this->getFilePathFromStreamPath($path);

        $stat = stat($filePath);
        \assert(false !== $stat);

        return $stat;
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        $this->position = 0;

        // the path is like "spout://csv_name" so the actual file name correspond the name of the host.
        $filePath = $this->getFilePathFromStreamPath($path);
        $resource = fopen($filePath, $mode);
        \assert(false !== $resource);
        $this->fileHandle = $resource;

        return true;
    }

    /**
     * @param positive-int $numBytes
     */
    public function stream_read(int $numBytes): string
    {
        $this->position += $numBytes;

        $fread = fread($this->fileHandle, $numBytes);
        \assert(false !== $fread);

        return $fread;
    }

    public function stream_tell(): int
    {
        return $this->position;
    }

    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        $result = fseek($this->fileHandle, $offset, $whence);
        if (-1 === $result) {
            return false;
        }

        if (SEEK_SET === $whence) {
            $this->position = $offset;
        } elseif (SEEK_CUR === $whence) {
            $this->position += $offset;
        }
        // not implemented

        return true;
    }

    public function stream_close(): bool
    {
        return fclose($this->fileHandle);
    }

    public function stream_eof(): bool
    {
        return feof($this->fileHandle);
    }

    private function getFilePathFromStreamPath(string $streamPath): string
    {
        $fileName = parse_url($streamPath, PHP_URL_HOST);

        return self::PATH_TO_CSV_RESOURCES.$fileName.self::CSV_EXTENSION;
    }
}
