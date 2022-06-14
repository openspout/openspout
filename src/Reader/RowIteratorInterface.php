<?php

declare(strict_types=1);

namespace OpenSpout\Reader;

use Iterator;
use OpenSpout\Common\Entity\Row;

/**
 * @extends Iterator<Row>
 *
 * @template-extends Iterator<int, null|Row>
 */
interface RowIteratorInterface extends Iterator
{
    public function current(): ?Row;
}
