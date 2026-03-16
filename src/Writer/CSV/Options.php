<?php

declare(strict_types=1);

namespace OpenSpout\Writer\CSV;

final readonly class Options
{
    /**
     * Note: There is no FIELD_ESCAPE option for the writer because PHP's fputcsv()
     * always escapes the enclosure character by doubling it, regardless of the
     * escape parameter. FIELD_ESCAPE is only available on the reader Options.
     *
     * @param non-empty-string $FIELD_DELIMITER
     * @param non-empty-string $FIELD_ENCLOSURE
     * @param positive-int     $FLUSH_THRESHOLD
     */
    public function __construct(
        public string $FIELD_DELIMITER = ',',
        public string $FIELD_ENCLOSURE = '"',
        public bool $SHOULD_ADD_BOM = true,
        public int $FLUSH_THRESHOLD = 500,
        public string $EOL = "\n",
    ) {}
}
