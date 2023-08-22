<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

use OpenSpout\Common\Exception\InvalidArgumentException;

final class RowAttributes
{
    private bool $visible;
    private bool $collapsed;

    /** @var null|int<0, 7> */
    private ?int $outlineLevel;

    /**
     * @param null|int<0, 7> $outlineLevel
     */
    public function __construct(?int $outlineLevel = null, bool $collapsed = false, bool $visible = true)
    {
        $this->setOutlineLevel($outlineLevel);
        $this->setCollapsed($collapsed);
        $this->setVisible($visible);
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    public function isCollapsed(): bool
    {
        return $this->collapsed;
    }

    public function setCollapsed(bool $collapsed): static
    {
        $this->collapsed = $collapsed;

        return $this;
    }

    /**
     * @return null|int<0, 7>
     */
    public function getOutlineLevel(): ?int
    {
        return $this->outlineLevel;
    }

    public function setOutlineLevel(?int $outlineLevel): static
    {
        if (null !== $outlineLevel && ($outlineLevel < 0 || $outlineLevel > 7)) {
            throw new InvalidArgumentException('RowAttributes level must range between 0 and 7.');
        }

        $this->outlineLevel = $outlineLevel;

        return $this;
    }

    public function isEmpty(): bool
    {
        return null === $this->getOutlineLevel()
            && false === $this->isCollapsed()
            && true === $this->isVisible();
    }
}
