<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common\Manager\Style;

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
