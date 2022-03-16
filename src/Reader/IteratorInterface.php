<?php

namespace OpenSpout\Reader;

use Iterator;

/**
 * @template T
 * @extends Iterator<T>
 */
interface IteratorInterface extends Iterator
{
    /**
     * Cleans up what was created to iterate over the object.
     */
    public function end(): void;
}
