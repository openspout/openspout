<?php

namespace OpenSpout\Common\Manager;

/**
 * Interface OptionsManagerInterface.
 */
interface OptionsManagerInterface
{
    /**
     * @param mixed $optionValue
     */
    public function setOption(string $optionName, $optionValue): void;

    /**
     * @return null|mixed The set option or NULL if no option with given name found
     */
    public function getOption(string $optionName): mixed;

    /**
     * Add an option to the internal list of options
     * Used only for mergeCells() for now.
     *
     * @param mixed $optionName
     * @param mixed $optionValue
     */
    public function addOption($optionName, $optionValue): void;
}
