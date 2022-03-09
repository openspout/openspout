<?php

namespace OpenSpout\Common\Helper;

use OpenSpout\Common\Exception\IOException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class FileSystemHelperTest extends TestCase
{
    /** @var FileSystemHelper */
    protected $fileSystemHelper;

    protected function setUp(): void
    {
        $baseFolder = sys_get_temp_dir();
        $this->fileSystemHelper = new FileSystemHelper($baseFolder);
    }

    public function testCreateFolderShouldThrowExceptionIfOutsideOfBaseFolder()
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Cannot perform I/O operation outside of the base folder');
        $this->fileSystemHelper->createFolder('/tmp/folder_outside_base_folder', 'folder_name');
    }

    public function testCreateFileWithContentsShouldThrowExceptionIfOutsideOfBaseFolder()
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Cannot perform I/O operation outside of the base folder');
        $this->fileSystemHelper->createFileWithContents('/tmp/folder_outside_base_folder', 'file_name', 'contents');
    }

    public function testDeleteFileShouldThrowExceptionIfOutsideOfBaseFolder()
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Cannot perform I/O operation outside of the base folder');
        $this->fileSystemHelper->deleteFile('/tmp/folder_outside_base_folder/file_name');
    }

    public function testDeleteFolderRecursivelyShouldThrowExceptionIfOutsideOfBaseFolder()
    {
        $this->expectException(IOException::class);
        $this->expectExceptionMessage('Cannot perform I/O operation outside of the base folder');

        $this->fileSystemHelper->deleteFolderRecursively('/tmp/folder_outside_base_folder');
    }
}
