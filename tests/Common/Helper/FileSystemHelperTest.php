<?php

declare(strict_types=1);

namespace OpenSpout\Common\Helper;

use OpenSpout\Common\Exception\IOException;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class FileSystemHelperTest extends TestCase
{
    private FileSystemHelper $fileSystemHelper;
    private string $baseFolder;

    protected function setUp(): void
    {
        $this->baseFolder = (new TestUsingResource())->getTempFolderPath();
        $this->fileSystemHelper = new FileSystemHelper($this->baseFolder);
    }

    public function testCreateFolderShouldThrowExceptionIfOutsideOfBaseFolder(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('outside of the base folder');
        $this->fileSystemHelper->createFolder(__DIR__, 'folder_name');
    }

    public function testCreateFolderShouldThrowExceptionIfParentDoesntExist(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Folder not found');
        $this->fileSystemHelper->createFolder('/tmp/folder_outside_base_folder', 'folder_name');
    }

    /**
     * @requires OSFAMILY Linux
     */
    public function testCreateFolderShouldThrowExceptionWhenFails(): void
    {
        self::assertTrue(chmod($this->baseFolder, 0500));
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Permission denied');
        $this->fileSystemHelper->createFolder($this->baseFolder, 'folder_name');
    }

    public function testCreateFileWithContentsShouldThrowExceptionIfOutsideOfBaseFolder(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Folder not found');
        $this->fileSystemHelper->createFileWithContents('/tmp/folder_outside_base_folder', 'file_name', 'contents');
    }

    /**
     * @requires OSFAMILY Linux
     */
    public function testCreateFileWithContentsShouldThrowExceptionIfFails(): void
    {
        self::assertTrue(chmod($this->baseFolder, 0500));
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Permission denied');
        $this->fileSystemHelper->createFileWithContents($this->baseFolder, 'folder_name', 'contents');
    }

    public function testDeleteFileShouldThrowExceptionIfOutsideOfBaseFolder(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Folder not found');
        $this->fileSystemHelper->deleteFile('/tmp/folder_outside_base_folder/file_name');
    }

    public function testDeleteFolderRecursivelyShouldThrowExceptionIfOutsideOfBaseFolder(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Folder not found');

        $this->fileSystemHelper->deleteFolderRecursively('/tmp/folder_outside_base_folder');
    }
}
