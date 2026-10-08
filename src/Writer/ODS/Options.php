<?php

declare(strict_types=1);

namespace OpenSpout\Writer\ODS;

use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Writer\Common\AbstractOptions;

final readonly class Options extends AbstractOptions
{
    /**
     * @param null|int $compressionLevel Level 0-9, or null for default
     */
    public function __construct(
        Style $FALLBACK_STYLE = new Style(),
        bool $SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY = true,
        ?float $DEFAULT_COLUMN_WIDTH = null,
        ?float $DEFAULT_ROW_HEIGHT = null,
        ?string $tempFolder = null,
        public ?int $compressionLevel = null,
    ) {
        if (null !== $compressionLevel && ($compressionLevel < 0 || $compressionLevel > 9)) {
            throw new InvalidArgumentException(\sprintf('Compression level must be between 0 and 9, %d given', $compressionLevel));
        }

        parent::__construct(
            $FALLBACK_STYLE,
            $SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY,
            $DEFAULT_COLUMN_WIDTH,
            $DEFAULT_ROW_HEIGHT,
            $tempFolder,
        );
    }
}
