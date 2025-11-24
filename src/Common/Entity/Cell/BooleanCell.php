<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Cell;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Comment\Comment;
use OpenSpout\Common\Entity\Style\Style;

final readonly class BooleanCell extends Cell
{
    private bool $value;

    public function __construct(
        bool $value,
        ?Style $style = null,
        ?Comment $comment = null,
    ) {
        parent::__construct($style, $comment);
        $this->value = $value;
    }

    public function getValue(): bool
    {
        return $this->value;
    }
}
