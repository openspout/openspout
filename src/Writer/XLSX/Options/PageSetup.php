<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Options;

final class PageSetup
{
    public function __construct(
        public readonly ?PageOrientation $pageOrientation,
        public readonly ?PaperSize $paperSize,
    ) {}
}
