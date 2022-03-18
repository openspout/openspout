<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX;

final class Options
{
    public string $TEMP_FOLDER;
    public bool $SHOULD_FORMAT_DATES = false;
    public bool $SHOULD_PRESERVE_EMPTY_ROWS = false;
    public bool $SHOULD_USE_1904_DATES = false;

    public function __construct()
    {
        $this->TEMP_FOLDER = sys_get_temp_dir();
    }
}
