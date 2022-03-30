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
}
