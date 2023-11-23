<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX;

use OpenSpout\Common\TempFolderOptionTrait;

final class Options
{
    use TempFolderOptionTrait;

    public bool $SHOULD_FORMAT_DATES = false;
    public bool $SHOULD_PRESERVE_EMPTY_ROWS = false;
    public bool $SHOULD_USE_1904_DATES = false;

    /**
     * @param array<string, mixed> $options Array of options
     *
     * @return static
     */
    public static function fromArray(array $options): static
    {
        $self = new self();
        $self->SHOULD_FORMAT_DATES = $options['SHOULD_FORMAT_DATES'] ?? $self->SHOULD_FORMAT_DATES;
        $self->SHOULD_PRESERVE_EMPTY_ROWS = $options['SHOULD_PRESERVE_EMPTY_ROWS'] ?? $self->SHOULD_PRESERVE_EMPTY_ROWS;
        $self->SHOULD_USE_1904_DATES = $options['SHOULD_USE_1904_DATES'] ?? $self->SHOULD_USE_1904_DATES;
        $self->setTempFolder($options['tempFolder'] ?? $self->getTempFolder());

        return $self;
    }
}
