<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX\Manager;

use OpenSpout\Reader\Exception\SharedStringNotFoundException;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\CachingStrategyFactory;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\FileBasedStrategy;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\InMemoryStrategy;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\MemoryLimit;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;
use ReflectionHelper;

/**
 * @internal
 */
final class SharedStringsManagerTest extends TestCase
{
    private ?SharedStringsManager $sharedStringsManager;

    protected function setUp(): void
    {
        $this->sharedStringsManager = null;
    }

    protected function tearDown(): void
    {
        if (null !== $this->sharedStringsManager) {
            $this->sharedStringsManager->cleanup();
        }
    }

    public function testGetStringAtIndexShouldThrowExceptionIfStringNotFound(): void
    {
        $this->expectException(SharedStringNotFoundException::class);

        $sharedStringsManager = $this->createSharedStringsManager();
        $sharedStringsManager->extractSharedStrings();
        $sharedStringsManager->getStringAtIndex(PHP_INT_MAX);
    }

    public function testGetStringAtIndexShouldReturnTheCorrectStringIfFound(): void
    {
        $sharedStringsManager = $this->createSharedStringsManager();
        $sharedStringsManager->extractSharedStrings();

        $sharedString = $sharedStringsManager->getStringAtIndex(0);
        self::assertSame('s1--A1', $sharedString);

        $sharedString = $sharedStringsManager->getStringAtIndex(24);
        self::assertSame('s1--E5', $sharedString);

        $usedCachingStrategy = ReflectionHelper::getValueOnObject($sharedStringsManager, 'cachingStrategy');
        self::assertInstanceOf(InMemoryStrategy::class, $usedCachingStrategy);
    }

    public function testGetStringAtIndexShouldWorkWithMultilineStrings(): void
    {
        $sharedStringsManager = $this->createSharedStringsManager('one_sheet_with_shared_multiline_strings.xlsx');

        $sharedStringsManager->extractSharedStrings();

        $sharedString = $sharedStringsManager->getStringAtIndex(0);
        self::assertSame("s1\nA1", $sharedString);

        $sharedString = $sharedStringsManager->getStringAtIndex(24);
        self::assertSame("s1\nE5", $sharedString);
    }

    public function testGetStringAtIndexShouldWorkWithStringsContainingTextAndHyperlinkInSameCell(): void
    {
        $sharedStringsManager = $this->createSharedStringsManager('one_sheet_with_shared_strings_containing_text_and_hyperlink_in_same_cell.xlsx');

        $sharedStringsManager->extractSharedStrings();

        $sharedString = $sharedStringsManager->getStringAtIndex(0);
        self::assertSame('go to https://github.com please', $sharedString);
    }

    public function testGetStringAtIndexShouldNotDoubleDecodeHTMLEntities(): void
    {
        $sharedStringsManager = $this->createSharedStringsManager('one_sheet_with_pre_encoded_html_entities.xlsx');

        $sharedStringsManager->extractSharedStrings();

        $sharedString = $sharedStringsManager->getStringAtIndex(0);
        self::assertSame('quote: &#34; - ampersand: &amp;', $sharedString);
    }

    public function testGetStringAtIndexWithFileBasedStrategy(): void
    {
        // force the file-based strategy by setting no memory limit
        $originalMemoryLimit = \ini_get('memory_limit');
        ini_set('memory_limit', '-1');

        $sharedStringsManager = $this->createSharedStringsManager('sheet_with_lots_of_shared_strings.xlsx');

        $sharedStringsManager->extractSharedStrings();

        $sharedString = $sharedStringsManager->getStringAtIndex(0);
        self::assertSame('str', $sharedString);

        $sharedString = $sharedStringsManager->getStringAtIndex(CachingStrategyFactory::MAX_NUM_STRINGS_PER_TEMP_FILE + 1);
        self::assertSame('str', $sharedString);

        $usedCachingStrategy = ReflectionHelper::getValueOnObject($sharedStringsManager, 'cachingStrategy');
        self::assertInstanceOf(FileBasedStrategy::class, $usedCachingStrategy);

        ini_set('memory_limit', $originalMemoryLimit);
    }

    private function createSharedStringsManager(string $resourceName = 'one_sheet_with_shared_strings.xlsx'): SharedStringsManager
    {
        $resourcePath = TestUsingResource::getResourcePath($resourceName);
        $cachingStrategyFactory = new CachingStrategyFactory(new MemoryLimit('1'));
        $workbookRelationshipsManager = new WorkbookRelationshipsManager($resourcePath);

        $options = new Options();
        $options->setTempFolder((new TestUsingResource())->getTempFolderPath());

        $this->sharedStringsManager = new SharedStringsManager(
            $resourcePath,
            $options,
            $workbookRelationshipsManager,
            $cachingStrategyFactory
        );

        return $this->sharedStringsManager;
    }
}
