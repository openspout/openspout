<?php

namespace OpenSpout\Writer\ODS\Manager;

use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Manager\OptionsManagerAbstract;
use OpenSpout\Writer\Common\Entity\Options;

/**
 * ODS Writer options manager.
 */
final class OptionsManager extends OptionsManagerAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function getSupportedOptions(): array
    {
        return [
            Options::TEMP_FOLDER,
            Options::DEFAULT_ROW_STYLE,
            Options::SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY,
            Options::DEFAULT_COLUMN_WIDTH,
            Options::DEFAULT_ROW_HEIGHT,
            Options::COLUMN_WIDTHS,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function setDefaultOptions(): void
    {
        $this->setOption(Options::TEMP_FOLDER, sys_get_temp_dir());
        $this->setOption(Options::DEFAULT_ROW_STYLE, new Style());
        $this->setOption(Options::SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY, true);
    }
}
