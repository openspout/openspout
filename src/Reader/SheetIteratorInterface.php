<?php

declare(strict_types=1);

namespace OpenSpout\Reader;

/**
 * Interface IteratorInterface.
 */
interface SheetIteratorInterface extends IteratorInterface
{
    public function end(): void;

    public function current(): ?SheetInterface;
}
