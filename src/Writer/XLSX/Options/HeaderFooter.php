<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Options;

final class HeaderFooter
{
    public function __construct(
        public readonly ?string $oddHeader,
        public readonly ?string $oddFooter,
        public readonly ?string $evenHeader,
        public readonly ?string $evenFooter,
        public readonly bool $differentOddEven = false,
    ) {}
}
