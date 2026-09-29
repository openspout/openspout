<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX\Manager\SharedStringsCaching;

use OpenSpout\Reader\Exception\SharedStringNotFoundException;

/**
 * This class implements the in-memory caching strategy for shared strings.
 * This strategy is used when the number of unique strings is low, compared to the memory available.
 *
 * @internal
 */
final class InMemoryStrategy implements CachingStrategyInterface
{
    /** @var array<int, string> Array used to cache the shared strings */
    private array $inMemoryCache = [];

    /** @var bool Whether the cache has been closed */
    private bool $isCacheClosed = false;

    /**
     * Adds the given string to the cache.
     *
     * @param string $sharedString      The string to be added to the cache
     * @param int    $sharedStringIndex Index of the shared string in the sharedStrings.xml file
     */
    public function addStringForIndex(string $sharedString, int $sharedStringIndex): void
    {
        if (!$this->isCacheClosed) {
            $this->inMemoryCache[$sharedStringIndex] = $sharedString;
        }
    }

    /**
     * Closes the cache after the last shared string was added.
     * This prevents any additional string from being added to the cache.
     */
    public function closeCache(): void
    {
        $this->isCacheClosed = true;
    }

    /**
     * Returns the string located at the given index from the cache.
     *
     * @param int $sharedStringIndex Index of the shared string in the sharedStrings.xml file
     *
     * @return string The shared string at the given index
     *
     * @throws SharedStringNotFoundException If no shared string found for the given index
     */
    public function getStringAtIndex(int $sharedStringIndex): string
    {
        return $this->inMemoryCache[$sharedStringIndex]
            ?? throw new SharedStringNotFoundException("Shared string not found for index: {$sharedStringIndex}");
    }

    /**
     * Destroys the cache, freeing memory and removing any created artifacts.
     */
    public function clearCache(): void
    {
        $this->inMemoryCache = [];
        $this->isCacheClosed = false;
    }
}
