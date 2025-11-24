<?php

declare(strict_types=1);

namespace OpenSpout\Common;

use OpenSpout\Common\Exception\InvalidArgumentException;

/**
 * @internal
 */
final readonly class TempFolderCheck
{
    /**
     * @param non-empty-string $tempFolder
     */
    public function assertTempFolder(string $tempFolder): void
    {
        if (is_dir($tempFolder) && is_writable($tempFolder)) {
            return;
        }

        throw new InvalidArgumentException("{$tempFolder} is not a writable folder");
    }
}
