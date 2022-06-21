<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common\Helper;

use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;
use ReflectionHelper;
use ZipArchive;

/**
 * @internal
 */
final class ZipHelperTest extends TestCase
{
    public function testLocalFilePathShouldBeNormalizedOnWindows(): void
    {
        $zipHelper = new ZipHelper();
        $zipMock = $this->getMockBuilder(ZipArchive::class)
            ->onlyMethods(['addFile', 'setCompressionName'])
            ->getMock()
        ;

        $tempFolder = (new TestUsingResource())->getTempFolderPath();
        mkdir($tempFolder.'/xl', 0700, true);
        touch($tempFolder.'/xl/workbook.xml');

        $rootFolderPath = $tempFolder;
        // File has Windows directory separator.
        $localFilePath = 'xl\workbook.xml';
        $existingFileMode = ZipHelper::EXISTING_FILES_OVERWRITE;
        $compressionMethod = ZipArchive::CM_DEFAULT;

        // Archived file should have Linux directory separator.
        $normalizedLocalFilePath = 'xl/workbook.xml';

        $zipMock->expects(self::once())
            ->method('addFile')
            ->with(self::anything(), $normalizedLocalFilePath)
        ;
        $zipMock->expects(self::once())
            ->method('setCompressionName')
            ->with($normalizedLocalFilePath, $compressionMethod)
        ;

        ReflectionHelper::callMethodOnObject(
            $zipHelper,
            'addFileToArchiveWithCompressionMethod',
            $zipMock,
            $rootFolderPath,
            $localFilePath,
            $existingFileMode,
            $compressionMethod,
        );

        unlink($tempFolder.'/xl/workbook.xml');
        rmdir($tempFolder.'/xl');
    }
}
