<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Cell;

use DateInterval;
use DateTimeImmutable;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Comment\Comment;
use OpenSpout\Common\Entity\Style\Style;

final readonly class FormulaCell extends Cell
{
    public function __construct(
        private string $value,
        private bool|DateInterval|DateTimeImmutable|float|int|string|null $computedValue = null,
        ?Style $style = null,
        ?Comment $comment = null,
    ) {
        parent::__construct($style, $comment);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getComputedValue(): bool|DateInterval|DateTimeImmutable|float|int|string|null
    {
        return $this->computedValue;
    }
}
