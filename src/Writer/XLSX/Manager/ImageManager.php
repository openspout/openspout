<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Manager;

/**
 * Tracks image paths across the whole workbook so that identical source files
 * are embedded in the XLSX archive exactly once, regardless of how many cells
 * reference them.
 *
 * @internal
 */
final class ImageManager
{
    /** @var array<string, int> Maps absolute path to its global media ID */
    private array $pathToId = [];

    private int $nextId = 1;

    /**
     * Returns true if this path has already been registered.
     * Call this *before* register() to decide whether the media file needs to
     * be copied.
     */
    public function has(string $path): bool
    {
        return isset($this->pathToId[$path]);
    }

    /**
     * Returns the global media ID for the given path, registering it with a
     * new ID if it has not been seen before.
     */
    public function register(string $path): int
    {
        if (!isset($this->pathToId[$path])) {
            $this->pathToId[$path] = $this->nextId++;
        }

        return $this->pathToId[$path];
    }
}
