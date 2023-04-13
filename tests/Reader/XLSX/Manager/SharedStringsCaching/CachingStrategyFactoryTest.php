<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX\Manager\SharedStringsCaching;

use OpenSpout\TestUsingResource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CachingStrategyFactoryTest extends TestCase
{
    public static function dataProviderForTestCreateBestCachingStrategy(): array
    {
        return [
            [null, '-1', FileBasedStrategy::class],
            [CachingStrategyFactory::MAX_NUM_STRINGS_PER_TEMP_FILE, '-1', FileBasedStrategy::class],
            [CachingStrategyFactory::MAX_NUM_STRINGS_PER_TEMP_FILE + 10, '-1', FileBasedStrategy::class],
            [CachingStrategyFactory::MAX_NUM_STRINGS_PER_TEMP_FILE - 10, '-1', InMemoryStrategy::class],
            [10, (CachingStrategyFactory::AMOUNT_MEMORY_NEEDED_PER_STRING_IN_KB * 10).'KB', FileBasedStrategy::class],
            [15, (CachingStrategyFactory::AMOUNT_MEMORY_NEEDED_PER_STRING_IN_KB * 10).'KB', FileBasedStrategy::class],
            [5, (CachingStrategyFactory::AMOUNT_MEMORY_NEEDED_PER_STRING_IN_KB * 10).'KB', InMemoryStrategy::class],
        ];
    }

    #[DataProvider('dataProviderForTestCreateBestCachingStrategy')]
    public function testCreateBestCachingStrategy(?int $sharedStringsUniqueCount, string $memoryLimitInKB, string $expectedStrategyClassName): void
    {
        $strategy = (new CachingStrategyFactory(new MemoryLimit($memoryLimitInKB)))
            ->createBestCachingStrategy($sharedStringsUniqueCount, (new TestUsingResource())->getTempFolderPath())
        ;

        self::assertSame($expectedStrategyClassName, $strategy::class);

        $strategy->clearCache();
    }

    public static function dataProviderForTestGetMemoryLimitInKB(): array
    {
        return [
            ['-1', -1],
            ['invalid', -1],
            ['1024B', 1],
            ['128K', 128],
            ['256KB', 256],
            ['512M', 512 * 1024],
            ['2MB', 2 * 1024],
            ['1G', 1 * 1024 * 1024],
            ['10GB', 10 * 1024 * 1024],
            ['2T', 2 * 1024 * 1024 * 1024],
            ['5TB', 5 * 1024 * 1024 * 1024],
        ];
    }

    #[DataProvider('dataProviderForTestGetMemoryLimitInKB')]
    public function testGetMemoryLimitInKB(string $memoryLimitFormatted, float $expectedMemoryLimitInKB): void
    {
        self::assertSame($expectedMemoryLimitInKB, (new MemoryLimit($memoryLimitFormatted))->getMemoryLimitInKB());
    }
}
