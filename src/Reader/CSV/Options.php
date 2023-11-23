<?php

declare(strict_types=1);

namespace OpenSpout\Reader\CSV;

use OpenSpout\Common\Helper\EncodingHelper;

final class Options
{
    public bool $SHOULD_PRESERVE_EMPTY_ROWS = false;
    public string $FIELD_DELIMITER = ',';
    public string $FIELD_ENCLOSURE = '"';
    public string $ENCODING = EncodingHelper::ENCODING_UTF8;

    /**
     * @param array<string, mixed> $options Array of options
     *
     * @return static
     */
    public static function fromArray(array $options): static
    {
        $self = new self();
        $self->SHOULD_PRESERVE_EMPTY_ROWS = $options['SHOULD_PRESERVE_EMPTY_ROWS'] ?? $self->SHOULD_PRESERVE_EMPTY_ROWS;
        $self->FIELD_DELIMITER = $options['FIELD_DELIMITER'] ?? $self->FIELD_DELIMITER;
        $self->FIELD_ENCLOSURE = $options['FIELD_ENCLOSURE'] ?? $self->FIELD_ENCLOSURE;
        $self->ENCODING = $options['ENCODING'] ?? $self->ENCODING;

        return $self;
    }
}
