<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Manager;

use FilesystemIterator;
use OpenSpout\Common\Helper\Escaper\XLSX as XLSXEscaper;
use OpenSpout\Common\Helper\StringHelper;
use OpenSpout\Writer\Common\Entity\Sheet;
use OpenSpout\Writer\Common\Entity\Worksheet;
use OpenSpout\Writer\Common\Manager\SheetManager;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionProperty;

/**
 * @internal
 */
final class CommentsManagerTest extends TestCase
{
    /**
     * @see https://github.com/openspout/openspout/issues/392
     */
    public function testCloseWorksheetCommentFilesShouldNotThrowTypeErrorOnFreedHandles(): void
    {
        // No value assertions: reaching this point without a TypeError is the goal.
        self::expectNotToPerformAssertions();

        $tempFolder = sys_get_temp_dir().'/openspout-comments-'.uniqid('', true);
        mkdir($tempFolder.'/drawings', 0o700, true);

        try {
            $sheetManager = new SheetManager(StringHelper::factory());
            $sheet = new Sheet(0, 'workbook-'.uniqid(), $sheetManager);
            $worksheet = new Worksheet($tempFolder.'/worksheets/sheet1.xml', $sheet);

            $commentsManager = new CommentsManager($tempFolder, new XLSXEscaper());
            $commentsManager->createWorksheetCommentFiles($worksheet);

            // Simulate PHP freeing the file handles during shutdown, so the pointers
            // stored inside CommentsManager become invalid resources.
            $this->closeStoredPointers($commentsManager, 'commentsFilePointers');
            $this->closeStoredPointers($commentsManager, 'drawingFilePointers');

            // Must not throw a TypeError even though the stored handles were freed.
            $commentsManager->closeWorksheetCommentFiles($worksheet);
        } finally {
            $this->removeFolderRecursively($tempFolder);
        }
    }

    private function closeStoredPointers(CommentsManager $manager, string $property): void
    {
        $reflection = new ReflectionProperty(CommentsManager::class, $property);

        foreach ($reflection->getValue($manager) as $pointer) {
            if (\is_resource($pointer)) {
                fclose($pointer);
            }
        }
    }

    private function removeFolderRecursively(string $folderPath): void
    {
        if (!is_dir($folderPath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folderPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        @rmdir($folderPath);
    }
}
