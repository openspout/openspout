<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity;

/**
 * This class defines a comment that can be added to a cell.
 */
final class Comment
{
    private string $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function getMessage() : string
    {
        return $this->message;
    }
}