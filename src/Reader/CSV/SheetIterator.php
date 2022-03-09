<?php

namespace OpenSpout\Reader\CSV;

use OpenSpout\Reader\SheetIteratorInterface;

/**
 * Class SheetIterator
 * Iterate over CSV unique "sheet".
 */
class SheetIterator implements SheetIteratorInterface
{
    /** @var Sheet The CSV unique "sheet" */
    protected $sheet;

    /** @var bool Whether the unique "sheet" has already been read */
    protected $hasReadUniqueSheet = false;

    /**
     * @param Sheet $sheet Corresponding unique sheet
     */
    public function __construct($sheet)
    {
        $this->sheet = $sheet;
    }

    /**
     * Rewind the Iterator to the first element.
     *
     * @see http://php.net/manual/en/iterator.rewind.php
     */
    public function rewind(): void
    {
        $this->hasReadUniqueSheet = false;
    }

    /**
     * Checks if current position is valid.
     *
     * @see http://php.net/manual/en/iterator.valid.php
     */
    public function valid(): bool
    {
        return !$this->hasReadUniqueSheet;
    }

    /**
     * Move forward to next element.
     *
     * @see http://php.net/manual/en/iterator.next.php
     */
    public function next(): void
    {
        $this->hasReadUniqueSheet = true;
    }

    /**
     * Return the current element.
     *
     * @see http://php.net/manual/en/iterator.current.php
     */
    public function current(): Sheet
    {
        return $this->sheet;
    }

    /**
     * Return the key of the current element.
     *
     * @see http://php.net/manual/en/iterator.key.php
     */
    public function key(): int
    {
        return 1;
    }

    /**
     * Cleans up what was created to iterate over the object.
     */
    public function end(): void
    {
        // do nothing
    }
}
