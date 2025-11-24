<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Comment;

use InvalidArgumentException;

/**
 * This class defines a comment that can be added to a cell.
 */
final readonly class Comment
{
    /**
     * @param list<TextRun> $textRuns
     */
    public function __construct(
        public string $height = '55.5pt',
        public string $width = '96pt',
        public string $marginLeft = '59.25pt',
        public string $marginTop = '1.5pt',
        public bool $visible = false,
        public string $fillColor = '#FFFFE1',
        public array $textRuns = [],
    ) {
        foreach ($this->textRuns as $index => $textRun) {
            if (!$textRun instanceof TextRun) {
                throw new InvalidArgumentException(\sprintf(
                    'TextRuns must be instance of %s, %s provided at index %s',
                    TextRun::class,
                    get_debug_type($textRun),
                    $index
                ));
            }
        }
    }
}
