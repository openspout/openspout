<?php

declare(strict_types=1);

namespace OpenSpout\Common;

use OpenSpout\Common\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class TempFolderOptionTraitTest extends TestCase
{
    public function testTempFolderMustBeWritable(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new TempFolderCheck())->assertTempFolder(uniqid(__DIR__));
    }
}
