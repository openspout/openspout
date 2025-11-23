<?php

declare(strict_types=1);

namespace OpenSpout\Writer\CSV;

final class Options
{
    public string $FIELD_DELIMITER = ',';
    public string $FIELD_ENCLOSURE = '"';
    public bool $SHOULD_ADD_BOM = true;
    public bool $SHOULD_LOCK_FILE_FOR_WRITING = false;

    /** @var positive-int */
    public int $FLUSH_THRESHOLD = 500;
}
