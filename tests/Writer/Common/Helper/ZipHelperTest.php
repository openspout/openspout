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
        mkdir($tempFolder.'/xl', 0o700, true);
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

    public function testCompressedFileUsesTheDefaultMethodWhenNoCompressionLevelIsGiven(): void
    {
        $zipMock = $this->getMockBuilder(ZipArchive::class)
            ->onlyMethods(['addFile', 'setCompressionName'])
            ->getMock()
        ;
        $zipMock->expects(self::once())
            ->method('setCompressionName')
            ->with('xl/workbook.xml', ZipArchive::CM_DEFAULT, 0)
        ;

        $this->withWorkbookFile(static function (string $rootFolderPath) use ($zipMock): void {
            (new ZipHelper())->addFileToArchive($zipMock, $rootFolderPath, 'xl/workbook.xml');
        });
    }

    public function testCompressedFileUsesDeflateAtTheGivenCompressionLevel(): void
    {
        $zipMock = $this->getMockBuilder(ZipArchive::class)
            ->onlyMethods(['addFile', 'setCompressionName'])
            ->getMock()
        ;
        $zipMock->expects(self::once())
            ->method('setCompressionName')
            ->with('xl/workbook.xml', ZipArchive::CM_DEFLATE, 1)
        ;

        $this->withWorkbookFile(static function (string $rootFolderPath) use ($zipMock): void {
            (new ZipHelper(compressionLevel: 1))->addFileToArchive($zipMock, $rootFolderPath, 'xl/workbook.xml');
        });
    }

    public function testUncompressedFileIsStoredWhateverTheCompressionLevel(): void
    {
        $zipMock = $this->getMockBuilder(ZipArchive::class)
            ->onlyMethods(['addFile', 'setCompressionName'])
            ->getMock()
        ;
        $zipMock->expects(self::once())
            ->method('setCompressionName')
            ->with('xl/workbook.xml', ZipArchive::CM_STORE, 0)
        ;

        $this->withWorkbookFile(static function (string $rootFolderPath) use ($zipMock): void {
            (new ZipHelper(compressionLevel: 1))->addUncompressedFileToArchive($zipMock, $rootFolderPath, 'xl/workbook.xml');
        });
    }

    public function testFolderFilesUseDeflateAtTheGivenCompressionLevel(): void
    {
        $zipMock = $this->getMockBuilder(ZipArchive::class)
            ->onlyMethods(['addFile', 'setCompressionName'])
            ->getMock()
        ;
        $zipMock->expects(self::once())
            ->method('setCompressionName')
            ->with('xl/workbook.xml', ZipArchive::CM_DEFLATE, 1)
        ;

        $this->withWorkbookFile(static function (string $rootFolderPath) use ($zipMock): void {
            (new ZipHelper(compressionLevel: 1))->addFolderToArchive($zipMock, $rootFolderPath);
        });
    }

    public function testFolderFilesKeepTheArchiveDefaultWhenNoCompressionLevelIsGiven(): void
    {
        $zipMock = $this->getMockBuilder(ZipArchive::class)
            ->onlyMethods(['addFile', 'setCompressionName'])
            ->getMock()
        ;
        $zipMock->expects(self::never())->method('setCompressionName');

        $this->withWorkbookFile(static function (string $rootFolderPath) use ($zipMock): void {
            (new ZipHelper())->addFolderToArchive($zipMock, $rootFolderPath);
        });
    }

    /**
     * @param callable(string): void $test receives the root folder holding xl/workbook.xml
     */
    private function withWorkbookFile(callable $test): void
    {
        $tempFolder = (new TestUsingResource())->getTempFolderPath();
        mkdir($tempFolder.'/xl', 0o700, true);
        touch($tempFolder.'/xl/workbook.xml');

        try {
            $test($tempFolder);
        } finally {
            unlink($tempFolder.'/xl/workbook.xml');
            rmdir($tempFolder.'/xl');
        }
    }
}
