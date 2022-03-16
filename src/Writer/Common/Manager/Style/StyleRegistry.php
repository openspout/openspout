<?php

namespace OpenSpout\Writer\Common\Manager\Style;

use OpenSpout\Common\Entity\Style\Style;

/**
 * Registry for all used styles.
 */
class StyleRegistry
{
    /** @var array<string, int> [SERIALIZED_STYLE] => [STYLE_ID] mapping table, keeping track of the registered styles */
    protected array $serializedStyleToStyleIdMappingTable = [];

    /** @var array<int, Style> [STYLE_ID] => [STYLE] mapping table, keeping track of the registered styles */
    protected array $styleIdToStyleMappingTable = [];

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
     * @return Style the registered style, updated with an internal ID
     */
    public function registerStyle(Style $style): Style
    {
        $serializedStyle = $this->serialize($style);

        if (!$this->hasSerializedStyleAlreadyBeenRegistered($serializedStyle)) {
            $nextStyleId = \count($this->serializedStyleToStyleIdMappingTable);
            $style->markAsRegistered($nextStyleId);

            $this->serializedStyleToStyleIdMappingTable[$serializedStyle] = $nextStyleId;
            $this->styleIdToStyleMappingTable[$nextStyleId] = $style;
        }

        return $this->getStyleFromSerializedStyle($serializedStyle);
    }

    /**
     * @return Style[] List of registered styles
     */
    public function getRegisteredStyles(): array
    {
        return array_values($this->styleIdToStyleMappingTable);
    }

    public function getStyleFromStyleId(int $styleId): Style
    {
        return $this->styleIdToStyleMappingTable[$styleId];
    }

    /**
     * Serializes the style for future comparison with other styles.
     * The ID is excluded from the comparison, as we only care about
     * actual style properties.
     *
     * @return string The serialized style
     */
    public function serialize(Style $style): string
    {
        // In order to be able to properly compare style, set static ID value and reset registration
        $currentId = $style->getId();
        $style->unmarkAsRegistered();

        $serializedStyle = serialize($style);

        $style->markAsRegistered($currentId);

        return $serializedStyle;
    }

    /**
     * Returns whether the serialized style has already been registered.
     *
     * @param string $serializedStyle The serialized style
     */
    protected function hasSerializedStyleAlreadyBeenRegistered(string $serializedStyle): bool
    {
        // Using isset here because it is way faster than array_key_exists...
        return isset($this->serializedStyleToStyleIdMappingTable[$serializedStyle]);
    }

    /**
     * Returns the registered style associated to the given serialization.
     *
     * @param string $serializedStyle The serialized style from which the actual style should be fetched from
     */
    protected function getStyleFromSerializedStyle(string $serializedStyle): Style
    {
        $styleId = $this->serializedStyleToStyleIdMappingTable[$serializedStyle];

        return $this->styleIdToStyleMappingTable[$styleId];
    }
}
