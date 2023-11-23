<?php

declare(strict_types=1);

namespace OpenSpout\Reader\ODS;

final class Options
{
    public bool $SHOULD_FORMAT_DATES = false;
    public bool $SHOULD_PRESERVE_EMPTY_ROWS = false;

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

        return $self;
    }
}
