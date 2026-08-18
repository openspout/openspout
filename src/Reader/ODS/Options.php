<?php

declare(strict_types=1);

namespace OpenSpout\Reader\ODS;

use OpenSpout\Reader\OptionsInterface;

final readonly class Options implements OptionsInterface
{
    public function __construct(
        public bool $SHOULD_FORMAT_DATES = false,
        public bool $SHOULD_PRESERVE_EMPTY_ROWS = false,
    ) {}
}
