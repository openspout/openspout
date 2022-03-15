<?php

declare(strict_types=1);

namespace OpenSpout\Reader;

use OpenSpout\Common\Entity\Row;

interface RowIteratorInterface extends IteratorInterface
{
    public function end(): void;

    public function current(): ?Row;
}
