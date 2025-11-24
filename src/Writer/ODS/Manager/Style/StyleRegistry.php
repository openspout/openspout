<?php

declare(strict_types=1);

namespace OpenSpout\Writer\ODS\Manager\Style;

use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\Common\Manager\Style\AbstractStyleRegistry as CommonStyleRegistry;

/**
 * @internal
 */
final class StyleRegistry extends CommonStyleRegistry
{
    /** @var array<non-empty-string, bool> [FONT_NAME] => [] Map whose keys contain all the fonts used */
    private array $usedFontsSet = [];

    /**
     * @return list<non-empty-string> List of used fonts name
     */
    public function getUsedFonts(): array
    {
        return array_keys($this->usedFontsSet);
    }

    protected function customRegisterStyle(int $styleId, Style $style): void
    {
        $this->usedFontsSet[$style->fontName] = true;
    }
}
