<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common\Manager\Style;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Style\Style;

/**
 * @internal
 */
abstract readonly class AbstractStyleManager implements StyleManagerInterface
{
    protected AbstractStyleRegistry $styleRegistry;

    public function __construct(AbstractStyleRegistry $styleRegistry)
    {
        $this->styleRegistry = $styleRegistry;
    }

    /**
     * Registers the given style as a used style.
     * Duplicate styles won't be registered more than once.
     *
     * @param Style $style The style to be registered
     */
    final public function registerStyle(Style $style): int
    {
        return $this->styleRegistry->registerStyle($style);
    }

    /**
     * Registers the style of the given cell and returns the ID to write it with.
     * Cells without a style use the default style (ID 0).
     * Multiline string cells get "wrap text" enabled, unless their style sets it explicitly.
     *
     * @return non-negative-int
     */
    final public function registerCellStyle(Cell $cell): int
    {
        $styleId = null !== $cell->style ? $this->styleRegistry->registerStyle($cell->style) : 0;

        /*
         * There is a bug on the Mac version of Excel (2011 and below) where new lines
         * are ignored even when the "wrap text" option is set. This only occurs with
         * inline strings (shared strings do work fine).
         * A workaround would be to encode "\n" as "_x000D_" but it does not work
         * on the Windows version of Excel...
         */
        if ($cell instanceof StringCell && str_contains($cell->getValue(), "\n")) {
            $styleId = $this->styleRegistry->getWrapTextStyleId($styleId);
        }

        return $styleId;
    }

    final public function get(): void {}

    /**
     * Returns the default style.
     *
     * @return Style Default style
     */
    final protected function getDefaultStyle(): Style
    {
        // By construction, the default style has ID 0
        return $this->styleRegistry->getRegisteredStyles()[0];
    }
}
