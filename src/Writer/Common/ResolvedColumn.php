<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common;

/**
 * A range of adjacent columns sharing an identical set of column attributes,
 * ready to be written as a single "<col>" element.
 *
 * @internal
 */
final readonly class ResolvedColumn
{
    /**
     * @param positive-int $start
     * @param positive-int $end
     * @param int<0, 7>    $outlineLevel
     */
    public function __construct(
        public int $start,
        public int $end,
        public ?float $width,
        public ?bool $hidden,
        public ?bool $collapsed,
        public ?int $outlineLevel,
    ) {}
}
