<?php

declare(strict_types = 1);

namespace OpenSpout\Reader;

use OpenSpout\Common\Entity\Row;

interface RowIteratorInterface extends IteratorInterface
{
    /**
     * Cleans up what was created to iterate over the object.
     *
     * @return void
     */
    public function end();

    /**
     * @return Row|null
     */
    #[\ReturnTypeWillChange]
    public function current();
}
