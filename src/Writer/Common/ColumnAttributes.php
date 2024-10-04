<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common;

/**
 * @internal
 */
final class ColumnAttributes
{
    public function __construct(
        public readonly ?int $outlineLevel = null,
        public readonly bool $collapsed = false,
        public readonly bool $hidden = false
    ) {
    }

    public function getOutlineLevel(): ?int
    {
        return $this->outlineLevel;
    }

    public function setOutlineLevel(?int $outlineLevel): void
    {
        $this->outlineLevel = $outlineLevel;
    }

    public function isCollapsed(): bool
    {
        return $this->collapsed;
    }

    public function setCollapsed(bool $collapsed): void
    {
        $this->collapsed = $collapsed;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    public function isEmpty(): bool
    {
        return null === $this->getOutlineLevel()
            && false === $this->isCollapsed()
            && false === $this->isHidden();
    }
}
