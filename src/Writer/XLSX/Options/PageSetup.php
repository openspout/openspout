<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Options;

final class PageSetup
{
    public bool $fitToPage = false;

    public function __construct(
        public readonly ?PageOrientation $pageOrientation,
        public readonly ?PaperSize $paperSize,
        public readonly ?int $fitToHeight = null,
        public readonly ?int $fitToWidth = null,
    ) {
        if(isset($fitToHeight) || isset($fitToWidth)) {
            $this->fitToPage = true;
        }
    }
}
