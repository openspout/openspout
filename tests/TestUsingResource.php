<?php

declare(strict_types=1);

namespace OpenSpout;

use PHPUnit\Framework\Assert;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * @internal
 */
final class TestUsingResource
{
    private const RESOURCES_PATH = 'tests/resources';

    /** @var string Path to the test generated resources folder */
    private string $generatedResourcesPath;

    /** @var string Path to the test resources folder, that does not have writing permissions */
    private string $generatedUnwritableResourcesPath;

    /** @var string Path to the test temp folder */
    private string $tempFolderPath;

    public function __construct()
    {
        $realpath = realpath(self::RESOURCES_PATH);
        \assert(false !== $realpath);
        $generatedPath = $realpath.\DIRECTORY_SEPARATOR.'generated_'.(string) getenv('TEST_TOKEN');
        $this->generatedResourcesPath = $generatedPath;
        $this->generatedUnwritableResourcesPath = $generatedPath.\DIRECTORY_SEPARATOR.'unwritable';
        $this->tempFolderPath = $generatedPath.\DIRECTORY_SEPARATOR.'temp';
    }

    /**
     * @return string Path of the resource who matches the given name or null if resource not found
     */
    public static function getResourcePath(string $resourceName): string
    {
        $resourceType = pathinfo($resourceName, PATHINFO_EXTENSION);

        return realpath(self::RESOURCES_PATH).\DIRECTORY_SEPARATOR.strtolower($resourceType).\DIRECTORY_SEPARATOR.$resourceName;
    }

    /**
     * @return string Path of the generated resource for the given name
     */
    public function getGeneratedResourcePath(string $resourceName): string
    {
        $resourceType = pathinfo($resourceName, PATHINFO_EXTENSION);
        $generatedResourcePathForType = $this->generatedResourcesPath.\DIRECTORY_SEPARATOR.strtolower($resourceType);
        if (!file_exists($generatedResourcePathForType)) {
            mkdir($generatedResourcePathForType, 0700, true);
        }

        return $generatedResourcePathForType.\DIRECTORY_SEPARATOR.$resourceName;
    }

    /**
     * @return string Path of the generated unwritable (because parent folder is read only) resource for the given name
     */
    public function getGeneratedUnwritableResourcePath(string $resourceName): string
    {
        // On Windows, chmod() or the mkdir's mode is ignored
        if ($this->isWindows()) {
            Assert::markTestSkipped('Skipping because Windows cannot create read-only folders through PHP');
        }

        if (!file_exists($this->generatedUnwritableResourcesPath)) {
            if (!file_exists($this->generatedResourcesPath)) {
                mkdir($this->generatedResourcesPath, 0700, true);
            }

            mkdir($this->generatedUnwritableResourcesPath, 0500, true);
        }

        return realpath($this->generatedUnwritableResourcesPath).\DIRECTORY_SEPARATOR.$resourceName;
    }

    /**
     * @return string Path of the temp folder
     */
    public function getTempFolderPath(): string
    {
        if (file_exists($this->tempFolderPath)) {
            $this->deleteFolderRecursively($this->tempFolderPath);
        }

        mkdir($this->tempFolderPath, 0700, true);

        return $this->tempFolderPath;
    }

    /**
     * @return bool Whether the OS on which PHP is installed is Windows
     */
    private function isWindows(): bool
    {
        return 'WIN' === strtoupper(substr(PHP_OS, 0, 3));
    }

    private function deleteFolderRecursively(string $folderPath): void
    {
        $itemIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folderPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($itemIterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($folderPath);
    }
}
