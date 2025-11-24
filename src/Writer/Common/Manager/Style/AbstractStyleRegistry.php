<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common\Manager\Style;

use OpenSpout\Common\Entity\Style\Style;

/**
 * @internal
 */
abstract class AbstractStyleRegistry
{
    /** @var array<string, non-negative-int> [SERIALIZED_STYLE] => [STYLE_ID] mapping table, keeping track of the registered styles */
    private array $serializedStyleToStyleIdMappingTable = [];

    /** @var array<int, Style> [STYLE_ID] => [STYLE] mapping table, keeping track of the registered styles */
    private array $styleIdToStyleMappingTable = [];

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
        $serializedStyle = spl_object_hash($style);
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
