<?php

namespace OpenSpout\Common\Helper;

use OpenSpout\Common\Exception\IOException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class FileSystemHelperTest extends TestCase
{
    protected FileSystemHelper $fileSystemHelper;

    protected function setUp(): void
    {
        $baseFolder = sys_get_temp_dir();
        $this->fileSystemHelper = new FileSystemHelper($baseFolder);
    }

    public function testCreateFolderShouldThrowExceptionIfOutsideOfBaseFolder(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Folder not found');
        $this->fileSystemHelper->createFolder('/tmp/folder_outside_base_folder', 'folder_name');
    }

    public function testCreateFileWithContentsShouldThrowExceptionIfOutsideOfBaseFolder(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Folder not found');
        $this->fileSystemHelper->createFileWithContents('/tmp/folder_outside_base_folder', 'file_name', 'contents');
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
