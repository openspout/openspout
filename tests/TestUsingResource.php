<?php

namespace OpenSpout;

/**
 * Trait TestUsingResource.
 */
trait TestUsingResource
{
    /** @var string Path to the test resources folder */
    private string $resourcesPath = 'tests/resources';

    /** @var string Path to the test generated resources folder */
    private string $generatedResourcesPath = 'tests/resources/generated';

    /** @var string Path to the test resources folder, that does not have writing permissions */
    private string $generatedUnwritableResourcesPath = 'tests/resources/generated/unwritable';

    /** @var string Path to the test temp folder */
    private string $tempFolderPath = 'tests/resources/generated/temp';

    /**
     * @return string Path of the resource who matches the given name or null if resource not found
     */
    protected function getResourcePath(string $resourceName): string
    {
        $resourceType = pathinfo($resourceName, PATHINFO_EXTENSION);

        return realpath($this->resourcesPath).'/'.strtolower($resourceType).'/'.$resourceName;
    }

    /**
     * @return string Path of the generated resource for the given name
     */
    protected function getGeneratedResourcePath(string $resourceName): string
    {
        $resourceType = pathinfo($resourceName, PATHINFO_EXTENSION);

        return realpath($this->generatedResourcesPath).'/'.strtolower($resourceType).'/'.$resourceName;
    }

    protected function createGeneratedFolderIfNeeded(string $resourceName)
    {
        $resourceType = pathinfo($resourceName, PATHINFO_EXTENSION);
        $generatedResourcePathForType = $this->generatedResourcesPath.'/'.strtolower($resourceType);

        if (!file_exists($generatedResourcePathForType)) {
            mkdir($generatedResourcePathForType, 0777, true);
        }
    }

    /**
     * @return string Path of the generated unwritable (because parent folder is read only) resource for the given name
     */
    protected function getGeneratedUnwritableResourcePath(string $resourceName): string
    {
        return realpath($this->generatedUnwritableResourcesPath).'/'.$resourceName;
    }

    protected function createUnwritableFolderIfNeeded()
    {
        // On Windows, chmod() or the mkdir's mode is ignored
        if ($this->isWindows()) {
            $this->markTestSkipped('Skipping because Windows cannot create read-only folders through PHP');
        }

        if (!file_exists($this->generatedUnwritableResourcesPath)) {
            // Make sure generated folder exists first
            if (!file_exists($this->generatedResourcesPath)) {
                mkdir($this->generatedResourcesPath, 0777, true);
            }

            // 0444 = read only
            mkdir($this->generatedUnwritableResourcesPath, 0444, true);
        }
    }

    /**
     * @return string Path of the temp folder
     */
    protected function getTempFolderPath(): string
    {
        return realpath($this->tempFolderPath);
    }

    protected function recreateTempFolder()
    {
        if (file_exists($this->tempFolderPath)) {
            $this->deleteFolderRecursively($this->tempFolderPath);
        }

        mkdir($this->tempFolderPath, 0777, true);
    }

    /**
     * @return bool Whether the OS on which PHP is installed is Windows
     */
    protected function isWindows(): bool
    {
        return 'WIN' === strtoupper(substr(PHP_OS, 0, 3));
    }

    private function deleteFolderRecursively(string $folderPath)
    {
        $itemIterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folderPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
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
