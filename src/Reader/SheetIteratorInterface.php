<?php

declare(strict_types=1);

namespace OpenSpout\Reader;

/**
 * @template T of SheetInterface
 * @extends IteratorInterface<T>
 */
interface SheetIteratorInterface extends IteratorInterface
{
    public function end(): void;

    /**
     * @return T of SheetInterface
     */
    public function current(): SheetInterface;
}
