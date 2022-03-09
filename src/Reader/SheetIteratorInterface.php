<?php

declare(strict_types = 1);

namespace OpenSpout\Reader;

/**
 * Interface IteratorInterface
 */
interface SheetIteratorInterface extends IteratorInterface
{
    /**
     * Cleans up what was created to iterate over the object.
     *
     * @return void
     */
    public function end();

    /**
     * @return SheetInterface|null
     */
    #[\ReturnTypeWillChange]
    public function current();
}
