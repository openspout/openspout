<?php

declare(strict_types=1);

namespace OpenSpout\Reader\CSV;

use OpenSpout\Common\Helper\EncodingHelper;

final readonly class Options
{
    public function __construct(
        public bool $SHOULD_PRESERVE_EMPTY_ROWS = false,
        public string $FIELD_DELIMITER = ',',
        public string $FIELD_ENCLOSURE = '"',
        public string $ENCODING = EncodingHelper::ENCODING_UTF8,
    ) {}
}
