<?php

declare(strict_types=1);

namespace OpenSpout\Writer\ODS\Entity;

use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Writer\Common\Entity\SheetViewInterface;

class ViewSettings implements SheetViewInterface
{
    /** @var non-negative-int */
    public int $freezeRow;

    /** @var non-negative-int */
    public int $freezeColumn;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        private readonly string $sheetName,
        int $freezeRow = 0,
        int $freezeColumn = 0,
        public bool $showGrid = true,
    ) {
        if ($freezeRow < 0) {
            throw new InvalidArgumentException('Freeze row must be a non-negative integer');
        }
        $this->freezeRow = $freezeRow;

        if ($freezeColumn < 0) {
            throw new InvalidArgumentException('Freeze column must be a non-negative integer');
        }
        $this->freezeColumn = $freezeColumn;
    }

    public function getXml(): string
    {
        return \sprintf(
            '<config:config-item-map-entry config:name="%s">%s</config:config-item-map-entry>',
            $this->sheetName,
            $this->getConfigItems()
        );
    }

    private function getConfigItems(): string
    {
        $configItems = $this->getFreezeConfigItemsXml();
        $configItems .= $this->getShowGridConfigItemsXml();

        return $configItems;
    }

    private function getFreezeConfigItemsXml(): string
    {
        return \sprintf(
            '
        <config:config-item config:name="HorizontalSplitMode" config:type="short">2</config:config-item>
        <config:config-item config:name="VerticalSplitMode" config:type="short">2</config:config-item>
        <config:config-item config:name="HorizontalSplitPosition" config:type="int">%1$d</config:config-item>
        <config:config-item config:name="VerticalSplitPosition" config:type="int">%2$d</config:config-item>
        <config:config-item config:name="PositionLeft" config:type="int">0</config:config-item>
        <config:config-item config:name="PositionRight" config:type="int">%1$d</config:config-item>
        <config:config-item config:name="PositionTop" config:type="int">0</config:config-item>
        <config:config-item config:name="PositionBottom" config:type="int">%2$d</config:config-item>',
            $this->freezeColumn,
            $this->freezeRow
        );
    }

    private function getShowGridConfigItemsXml(): string
    {
        return \sprintf(
            '<config:config-item config:name="ShowGrid" config:type="boolean">%s</config:config-item>',
            $this->showGrid ? 'true' : 'false'
        );
    }
}
