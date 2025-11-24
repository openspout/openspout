<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Comment;

/**
 * This class defines rich text in a fluent interface that can be added to a comment.
 */
final readonly class TextRun
{
    public function __construct(
        public string $text,
        public int $fontSize = 10,
        public string $fontColor = '000000',
        public string $fontName = 'Tahoma',
        public bool $bold = false,
        public bool $italic = false,
    ) {}
}
