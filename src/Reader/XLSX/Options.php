<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX;

use OpenSpout\Common\TempFolderCheck;

final readonly class Options
{
    /** @var non-empty-string */
    public string $tempFolder;

    /**
     * @param null|non-empty-string $tempFolder
     */
    public function __construct(
        public bool $SHOULD_FORMAT_DATES = false,
        public bool $SHOULD_PRESERVE_EMPTY_ROWS = false,
        public bool $SHOULD_USE_1904_DATES = false,
        public bool $SHOULD_LOAD_MERGE_CELLS = false,
        ?string $tempFolder = null,
    ) {
        $tempFolder ??= sys_get_temp_dir();
        \assert('' !== $tempFolder);
        $this->tempFolder = $tempFolder;
        (new TempFolderCheck())->assertTempFolder($this->tempFolder);
    }

    public function withShouldUse1904Dates(bool $SHOULD_USE_1904_DATES): self
    {
        $values = get_object_vars($this);
        $values['SHOULD_USE_1904_DATES'] = $SHOULD_USE_1904_DATES;

        return new self(...$values);
    }
}
