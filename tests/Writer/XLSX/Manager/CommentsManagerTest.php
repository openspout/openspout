<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Manager;

use OpenSpout\Common\Helper\Escaper;
use OpenSpout\Common\Helper\StringHelper;
use OpenSpout\Writer\Common\Entity\Sheet;
use OpenSpout\Writer\Common\Entity\Worksheet;
use OpenSpout\Writer\Common\Manager\SheetManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CommentsManager::class)]
final class CommentsManagerTest extends TestCase
{

    public function test_closeWorksheetCommentFiles_does_not_throw_when_resources_are_invalid(): void
    {
        $tempDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.'openspout_test_'.uniqid();
        mkdir($tempDir.\DIRECTORY_SEPARATOR.'drawings', 0777, true);

        $manager = new CommentsManager($tempDir, new Escaper\XLSX());

        $sheetManager = new SheetManager(StringHelper::factory());
        $sheet = new Sheet(0, 'workbook1', $sheetManager);
        $worksheet = new Worksheet('/tmp/sheet1.xml', $sheet);

        $manager->createWorksheetCommentFiles($worksheet);

        // Simulate PHP shutdown: externally invalidate the stored file handles.
        $reflection = new \ReflectionClass($manager);

        foreach ($reflection->getProperty('commentsFilePointers')->getValue($manager) as $fp) {
            if (is_resource($fp)) fclose($fp);
        }
        foreach ($reflection->getProperty('drawingFilePointers')->getValue($manager) as $fp) {
            if (is_resource($fp)) fclose($fp);
        }

        // Before fix: TypeError — fwrite(): supplied resource is not a valid stream resource
        // After fix: completes without exception
        $exceptionThrown = false;
        try {
            $manager->closeWorksheetCommentFiles($worksheet);
        } catch (\TypeError $e) {
            $exceptionThrown = true;
        }

        self::assertFalse($exceptionThrown, 'closeWorksheetCommentFiles() should not throw when file handles are no longer valid resources');

        // Cleanup
        foreach (glob($tempDir.\DIRECTORY_SEPARATOR.'*.xml') ?: [] as $f) @unlink($f);
        foreach (glob($tempDir.\DIRECTORY_SEPARATOR.'drawings'.\DIRECTORY_SEPARATOR.'*') ?: [] as $f) @unlink($f);
        @rmdir($tempDir.\DIRECTORY_SEPARATOR.'drawings');
        @rmdir($tempDir);
    }
}