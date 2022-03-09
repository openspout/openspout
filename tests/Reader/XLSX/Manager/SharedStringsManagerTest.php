<?php

namespace OpenSpout\Reader\XLSX\Manager;

use OpenSpout\Reader\Exception\SharedStringNotFoundException;
use OpenSpout\Reader\XLSX\Creator\HelperFactory;
use OpenSpout\Reader\XLSX\Creator\InternalEntityFactory;
use OpenSpout\Reader\XLSX\Creator\ManagerFactory;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\CachingStrategyFactory;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\FileBasedStrategy;
use OpenSpout\Reader\XLSX\Manager\SharedStringsCaching\InMemoryStrategy;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SharedStringsManagerTest extends TestCase
{
    use TestUsingResource;

    /** @var null|SharedStringsManager */
    private $sharedStringsManager;

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

    public function testGetStringAtIndexShouldThrowExceptionIfStringNotFound()
    {
        $this->expectException(SharedStringNotFoundException::class);

        $sharedStringsManager = $this->createSharedStringsManager();
        $sharedStringsManager->extractSharedStrings();
        $sharedStringsManager->getStringAtIndex(PHP_INT_MAX);
    }

    public function testGetStringAtIndexShouldReturnTheCorrectStringIfFound()
    {
        $sharedStringsManager = $this->createSharedStringsManager();
        $sharedStringsManager->extractSharedStrings();

        $sharedString = $sharedStringsManager->getStringAtIndex(0);
        static::assertSame('s1--A1', $sharedString);

        $sharedString = $sharedStringsManager->getStringAtIndex(24);
        static::assertSame('s1--E5', $sharedString);

        $usedCachingStrategy = \ReflectionHelper::getValueOnObject($sharedStringsManager, 'cachingStrategy');
        static::assertTrue($usedCachingStrategy instanceof InMemoryStrategy);
    }

    public function testGetStringAtIndexShouldWorkWithMultilineStrings()
    {
        $sharedStringsManager = $this->createSharedStringsManager('one_sheet_with_shared_multiline_strings.xlsx');

        $sharedStringsManager->extractSharedStrings();

        $sharedString = $sharedStringsManager->getStringAtIndex(0);
        static::assertSame("s1\nA1", $sharedString);

        $sharedString = $sharedStringsManager->getStringAtIndex(24);
        static::assertSame("s1\nE5", $sharedString);
    }

    public function testGetStringAtIndexShouldWorkWithStringsContainingTextAndHyperlinkInSameCell()
    {
        $sharedStringsManager = $this->createSharedStringsManager('one_sheet_with_shared_strings_containing_text_and_hyperlink_in_same_cell.xlsx');

        $sharedStringsManager->extractSharedStrings();

        $sharedString = $sharedStringsManager->getStringAtIndex(0);
        static::assertSame('go to https://github.com please', $sharedString);
    }

    public function testGetStringAtIndexShouldNotDoubleDecodeHTMLEntities()
    {
        $sharedStringsManager = $this->createSharedStringsManager('one_sheet_with_pre_encoded_html_entities.xlsx');

        $sharedStringsManager->extractSharedStrings();

        $sharedString = $sharedStringsManager->getStringAtIndex(0);
        static::assertSame('quote: &#34; - ampersand: &amp;', $sharedString);
    }

    public function testGetStringAtIndexWithFileBasedStrategy()
    {
        // force the file-based strategy by setting no memory limit
        $originalMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '-1');

        $sharedStringsManager = $this->createSharedStringsManager('sheet_with_lots_of_shared_strings.xlsx');

        $sharedStringsManager->extractSharedStrings();

        $sharedString = $sharedStringsManager->getStringAtIndex(0);
        static::assertSame('str', $sharedString);

        $sharedString = $sharedStringsManager->getStringAtIndex(CachingStrategyFactory::MAX_NUM_STRINGS_PER_TEMP_FILE + 1);
        static::assertSame('str', $sharedString);

        $usedCachingStrategy = \ReflectionHelper::getValueOnObject($sharedStringsManager, 'cachingStrategy');
        static::assertTrue($usedCachingStrategy instanceof FileBasedStrategy);

        ini_set('memory_limit', $originalMemoryLimit);
    }

    /**
     * @param string $resourceName
     *
     * @return SharedStringsManager
     */
    private function createSharedStringsManager($resourceName = 'one_sheet_with_shared_strings.xlsx')
    {
        $resourcePath = $this->getResourcePath($resourceName);
        $tempFolder = sys_get_temp_dir();
        $cachingStrategyFactory = new CachingStrategyFactory();
        $helperFactory = new HelperFactory();
        $managerFactory = new ManagerFactory($helperFactory, $cachingStrategyFactory);
        $entityFactory = new InternalEntityFactory($managerFactory, $helperFactory);
        $workbookRelationshipsManager = new WorkbookRelationshipsManager($resourcePath, $entityFactory);

        $this->sharedStringsManager = new SharedStringsManager(
            $resourcePath,
            $tempFolder,
            $workbookRelationshipsManager,
            $entityFactory,
            $helperFactory,
            $cachingStrategyFactory
        );

        return $this->sharedStringsManager;
    }
}
