<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Validation;

final readonly class ValidationDisplay
{
    public function __construct(
        public bool $allowBlank = true,
        public bool $showInputMessage = true,
        public bool $showErrorMessage = true,
        public ErrorStyle $errorStyle = ErrorStyle::Stop,
        public ?string $promptTitle = null,
        public ?string $prompt = null,
        public ?string $errorTitle = null,
        public ?string $error = null,
    ) {}
}
