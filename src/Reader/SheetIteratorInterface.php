<?php

declare(strict_types=1);

namespace OpenSpout\Reader;

/**
 * @extends IteratorInterface<SheetInterface>
 */
interface SheetIteratorInterface extends IteratorInterface
{
    public function end(): void;

    public function current(): ?SheetInterface;
}
