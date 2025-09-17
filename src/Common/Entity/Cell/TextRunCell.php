<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Cell;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Comment\TextRun;

final class TextRunCell extends Cell
{
    /** @var TextRun[] */
    private array $value = [];

    public function __construct(?array $value)
    {
        $this->value = $value;
        parent::__construct(null);
    }

    public function addTextRun(?TextRun $textRun)
    {
        $this->value[] = $textRun;
    }

    public function getTextRuns(): array
    {
        return $this->value;
    }

    public function getValue(): array
    {
        return $this->getTextRuns();
    }
}
