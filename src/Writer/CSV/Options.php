<?php

declare(strict_types=1);

namespace OpenSpout\Writer\CSV;

final class Options
{
    public string $FIELD_DELIMITER = ',';
    public string $FIELD_ENCLOSURE = '"';
    public bool $SHOULD_ADD_BOM = true;

    /** @var positive-int */
    public int $FLUSH_THRESHOLD = 500;

    /**
     * @param array<string, mixed> $options Array of options
     *
     * @return static
     */
    public static function fromArray(array $options): static
    {
        $self = new self();
        $self->FIELD_DELIMITER = $options['FIELD_DELIMITER'] ?? $self->FIELD_DELIMITER;
        $self->FIELD_ENCLOSURE = $options['FIELD_ENCLOSURE'] ?? $self->FIELD_ENCLOSURE;
        $self->SHOULD_ADD_BOM = $options['SHOULD_ADD_BOM'] ?? $self->SHOULD_ADD_BOM;
        $self->FLUSH_THRESHOLD = $options['FLUSH_THRESHOLD'] ?? $self->FLUSH_THRESHOLD;

        return $self;
    }
}
