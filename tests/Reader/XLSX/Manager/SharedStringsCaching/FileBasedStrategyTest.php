<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX\Manager\SharedStringsCaching;

use OpenSpout\Reader\Exception\SharedStringNotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class FileBasedStrategyTest extends TestCase
{
    private FileBasedStrategy $strategy;

    protected function setUp(): void
    {
        $tempFolder = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('tmp_'.(string) getenv('TEST_TOKEN'));
        self::assertNotFalse(mkdir($tempFolder));
        $this->strategy = new FileBasedStrategy($tempFolder, 999);
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
