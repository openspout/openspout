<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Cell;

use DateTimeInterface;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Comment\Comment;
use OpenSpout\Common\Entity\Style\Style;

final readonly class DateTimeCell extends Cell
{
    private DateTimeInterface $value;

    public function __construct(
        DateTimeInterface $value,
        ?Style $style = null,
        ?Comment $comment = null,
    ) {
        parent::__construct($style, $comment);
        $this->value = $value;
    }

    public function getValue(): DateTimeInterface
    {
        return $this->value;
    }
}
