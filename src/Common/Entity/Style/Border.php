<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Style;

final readonly class Border
{
    /** @var array<non-empty-string, BorderPart> */
    private array $parts;

    public function __construct(BorderPart ...$borderParts)
    {
        $parts = [];
        foreach ($borderParts as $borderPart) {
            $parts[$borderPart->name->value] = $borderPart;
        }
        $this->parts = $parts;
    }

    public function getPart(BorderName $name): ?BorderPart
    {
        return $this->parts[$name->value] ?? null;
    }

    /**
     * @return array<non-empty-string, BorderPart>
     */
    public function getParts(): array
    {
        return $this->parts;
    }
}
