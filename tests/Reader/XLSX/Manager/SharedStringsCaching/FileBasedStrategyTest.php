<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX\Manager\SharedStringsCaching;

use OpenSpout\Reader\Exception\SharedStringNotFoundException;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class FileBasedStrategyTest extends TestCase
{
    private FileBasedStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new FileBasedStrategy((new TestUsingResource())->getTempFolderPath(), 999);
    }

    public function testUninitializedFileRaisesException(): void
    {
        $this->expectException(SharedStringNotFoundException::class);
        $this->strategy->getStringAtIndex(1);
    }

    public function testUnknownSharedStringRaisesException(): void
    {
        $sharedString = uniqid();
        $this->strategy->addStringForIndex($sharedString, 0);
        $this->strategy->closeCache();
        self::assertSame($sharedString, $this->strategy->getStringAtIndex(0));

        $this->expectException(SharedStringNotFoundException::class);
        $this->strategy->getStringAtIndex(99);
    }

    public function testPagedStrings(): void
    {
        $this->strategy->addStringForIndex('a', 0);
        $this->strategy->addStringForIndex('b', 999);
        $this->strategy->addStringForIndex('c', 999 * 2);
        $this->strategy->closeCache();
        self::assertSame('a', $this->strategy->getStringAtIndex(0));
        self::assertSame('b', $this->strategy->getStringAtIndex(999));
        self::assertSame('c', $this->strategy->getStringAtIndex(999 * 2));
    }
}
