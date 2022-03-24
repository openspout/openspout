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
        $options = new class() {
            use TempFolderOptionTrait;
        };
        $this->expectException(InvalidArgumentException::class);

        $options->setTempFolder(uniqid(__DIR__));
    }
}
