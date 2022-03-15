<?php

namespace OpenSpout\Writer\XLSX\Manager;

use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Manager\OptionsManagerAbstract;
use OpenSpout\Writer\Common\Entity\Options;

/**
 * XLSX Writer options manager.
 */
class OptionsManager extends OptionsManagerAbstract
{
    /** Default style font values */
    public const DEFAULT_FONT_SIZE = 12;
    public const DEFAULT_FONT_NAME = 'Calibri';

    /**
     * {@inheritdoc}
     */
    protected function getSupportedOptions(): array
    {
        return [
            Options::TEMP_FOLDER,
            Options::DEFAULT_ROW_STYLE,
            Options::SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY,
            Options::SHOULD_USE_INLINE_STRINGS,
            Options::DEFAULT_COLUMN_WIDTH,
            Options::DEFAULT_ROW_HEIGHT,
            Options::COLUMN_WIDTHS,
            Options::MERGE_CELLS,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function setDefaultOptions()
    {
        $defaultRowStyle = (new Style())
            ->setFontSize(self::DEFAULT_FONT_SIZE)
            ->setFontName(self::DEFAULT_FONT_NAME)
        ;

        $this->setOption(Options::TEMP_FOLDER, sys_get_temp_dir());
        $this->setOption(Options::DEFAULT_ROW_STYLE, $defaultRowStyle);
        $this->setOption(Options::SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY, true);
        $this->setOption(Options::SHOULD_USE_INLINE_STRINGS, true);
        $this->setOption(Options::MERGE_CELLS, []);
    }
}
