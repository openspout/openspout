<?php

namespace OpenSpout\Common\Creator;

use OpenSpout\Common\Helper\FileSystemHelper;
use OpenSpout\Common\Helper\StringHelper;

/**
 * Factory to create helpers.
 */
class HelperFactory
{
    /**
     * @param string $baseFolderPath The path of the base folder where all the I/O can occur
     *
     * @return FileSystemHelper
     */
    public function createFileSystemHelper($baseFolderPath)
    {
        return new FileSystemHelper($baseFolderPath);
    }

    /**
     * @return StringHelper
     */
    public function createStringHelper()
    {
        return new StringHelper();
    }
}
