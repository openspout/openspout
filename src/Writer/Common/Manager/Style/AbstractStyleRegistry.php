<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common\Manager\Style;

use OpenSpout\Common\Entity\Style\Style;

/**
 * @internal
 */
abstract class AbstractStyleRegistry
{
    /** @var array<int, non-negative-int> [SERIALIZED_STYLE] => [STYLE_ID] mapping table, keeping track of the registered styles */
    private array $serializedStyleToStyleIdMappingTable = [];

    /** @var array<int, Style> [STYLE_ID] => [STYLE] mapping table, keeping track of the registered styles */
    private array $styleIdToStyleMappingTable = [];

    /** @var array<int, non-negative-int> [STYLE_ID] => [WRAP_TEXT_STYLE_ID] mapping table, keeping track of the styles registered for multiline cells */
    private array $styleIdToWrapTextStyleIdMappingTable = [];

    public function __construct(Style $defaultStyle)
    {
        // This ensures that the default style is the first one to be registered
        $this->registerStyle($defaultStyle);
    }

    /**
     * Registers the given style as a used style.
     * Duplicate styles won't be registered more than once.
     *
     * @param Style $style The style to be registered
     *
     * @return non-negative-int
     */
    final public function registerStyle(Style $style): int
    {
        $serializedStyle = spl_object_id($style);
        if (\array_key_exists($serializedStyle, $this->serializedStyleToStyleIdMappingTable)) {
            return $this->serializedStyleToStyleIdMappingTable[$serializedStyle];
        }

        $nextStyleId = \count($this->serializedStyleToStyleIdMappingTable);
        $this->serializedStyleToStyleIdMappingTable[$serializedStyle] = $nextStyleId;
        $this->styleIdToStyleMappingTable[$nextStyleId] = $style;
        $this->customRegisterStyle($nextStyleId, $style);

        return $nextStyleId;
    }

    /**
     * Returns the ID of the style to use for a multiline cell having the given style:
     * the same style with "wrap text" enabled, unless the style sets "wrap text" explicitly.
     * The wrapping style is registered only once per style.
     *
     * @param non-negative-int $styleId
     *
     * @return non-negative-int
     */
    final public function getWrapTextStyleId(int $styleId): int
    {
        if (!\array_key_exists($styleId, $this->styleIdToWrapTextStyleIdMappingTable)) {
            $style = $this->styleIdToStyleMappingTable[$styleId];
            $this->styleIdToWrapTextStyleIdMappingTable[$styleId] = null === $style->shouldWrapText
                ? $this->registerStyle($style->withShouldWrapText(true))
                : $styleId;
        }

        return $this->styleIdToWrapTextStyleIdMappingTable[$styleId];
    }

    /**
     * @return array<int, Style> List of registered styles
     */
    final public function getRegisteredStyles(): array
    {
        return $this->styleIdToStyleMappingTable;
    }

    final public function getStyleFromStyleId(int $styleId): Style
    {
        return $this->styleIdToStyleMappingTable[$styleId];
    }

    protected function customRegisterStyle(int $styleId, Style $style): void {}
}
