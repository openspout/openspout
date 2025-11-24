<?php

declare(strict_types=1);

namespace OpenSpout\Reader\ODS;

final readonly class Options
{
    public function __construct(
        public bool $SHOULD_FORMAT_DATES = false,
        public bool $SHOULD_PRESERVE_EMPTY_ROWS = false,
    ) {}
}
