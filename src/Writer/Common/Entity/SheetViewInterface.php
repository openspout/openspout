<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common\Entity;

interface SheetViewInterface
{
    /**
     * Returns the format-specific serialization of the sheet view configuration.
     *
     * The file format layer is responsible for persisting this value into
     * the target document structure.
     *
     * @return non-empty-string Format-specific representation of sheet view settings
     */
    public function getXml(): string;
}
