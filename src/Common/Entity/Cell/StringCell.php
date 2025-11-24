<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Cell;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Comment\Comment;
use OpenSpout\Common\Entity\Style\Style;

final readonly class StringCell extends Cell
{
    private string $value;

    public function __construct(
        string $value,
        ?Style $style = null,
        ?Comment $comment = null,
    ) {
        /*
         * There is a bug on the Mac version of Excel (2011 and below) where new lines
         * are ignored even when the "wrap text" option is set. This only occurs with
         * inline strings (shared strings do work fine).
         * A workaround would be to encode "\n" as "_x000D_" but it does not work
         * on the Windows version of Excel...
         */
        if (true !== $style?->shouldWrapText && str_contains($value, "\n")) {
            $style = ($style ?? new Style())->withShouldWrapText(true);
        }

        parent::__construct($style, $comment);
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
