<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Validation;

final readonly class SerializedValidationRule
{
    public function __construct(
        public string $type,
        public ?string $operator,
        public string $formula1,
        public ?string $formula2 = null,
    ) {}
}
