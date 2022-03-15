<?php

namespace OpenSpout\Writer\Common\Entity;

/**
 * Entity describing a workbook.
 */
final class Workbook
{
    /** @var Worksheet[] List of the workbook's sheets */
    private array $worksheets = [];

    /** @var string Timestamp based unique ID identifying the workbook */
    private string $internalId;

    /**
     * Workbook constructor.
     */
    public function __construct()
    {
        $this->internalId = uniqid();
    }

    /**
     * @return Worksheet[]
     */
    public function getWorksheets(): array
    {
        return $this->worksheets;
    }

    /**
     * @param Worksheet[] $worksheets
     */
    public function setWorksheets(array $worksheets)
    {
        $this->worksheets = $worksheets;
    }

    public function getInternalId(): string
    {
        return $this->internalId;
    }
}
