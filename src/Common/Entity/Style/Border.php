<?php

namespace OpenSpout\Common\Entity\Style;

final class Border
{
    public const LEFT = 'left';
    public const RIGHT = 'right';
    public const TOP = 'top';
    public const BOTTOM = 'bottom';

    public const STYLE_NONE = 'none';
    public const STYLE_SOLID = 'solid';
    public const STYLE_DASHED = 'dashed';
    public const STYLE_DOTTED = 'dotted';
    public const STYLE_DOUBLE = 'double';

    public const WIDTH_THIN = 'thin';
    public const WIDTH_MEDIUM = 'medium';
    public const WIDTH_THICK = 'thick';

    /** @var array A list of BorderPart objects for this border. */
    private array $parts = [];

    public function __construct(array $borderParts = [])
    {
        $this->setParts($borderParts);
    }

    /**
     * @param string $name The name of the border part
     */
    public function getPart(string $name): ?BorderPart
    {
        return $this->hasPart($name) ? $this->parts[$name] : null;
    }

    /**
     * @param string $name The name of the border part
     */
    public function hasPart(string $name): bool
    {
        return isset($this->parts[$name]);
    }

    public function getParts(): array
    {
        return $this->parts;
    }

    /**
     * Set BorderParts.
     */
    public function setParts(array $parts)
    {
        $this->parts = [];
        foreach ($parts as $part) {
            $this->addPart($part);
        }
    }

    public function addPart(BorderPart $borderPart): self
    {
        $this->parts[$borderPart->getName()] = $borderPart;

        return $this;
    }
}
