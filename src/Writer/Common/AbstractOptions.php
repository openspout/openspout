<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common;

use OpenSpout\Common\Entity\Style\Style;

abstract class AbstractOptions
{
    public string $TEMP_FOLDER;
    public Style $DEFAULT_ROW_STYLE;
    public bool $SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY = true;
    public ?float $DEFAULT_COLUMN_WIDTH = null;
    public ?float $DEFAULT_ROW_HEIGHT = null;

    /** @var array<array-key, array<array-key, float|int>> Array of min-max-width arrays */
    public array $COLUMN_WIDTHS = [];

    public function __construct()
    {
        $this->TEMP_FOLDER = sys_get_temp_dir();
        $this->DEFAULT_ROW_STYLE = new Style();
    }
}
