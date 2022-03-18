<?php

declare(strict_types=1);

namespace OpenSpout\Common\Manager;

abstract class OptionsManagerAbstract implements OptionsManagerInterface
{
    public const PREFIX_OPTION = 'OPTION_';

    /** @var string[] List of all supported option names */
    private array $supportedOptions = [];

    /** @var array<string, mixed> Associative array [OPTION_NAME => OPTION_VALUE] */
    private array $options = [];

    /**
     * OptionsManagerAbstract constructor.
     */
    public function __construct()
    {
        $this->supportedOptions = $this->getSupportedOptions();
        $this->setDefaultOptions();
    }

    /**
     * Sets the given option, if this option is supported.
     */
    public function setOption(string $optionName, mixed $optionValue): void
    {
        if (\in_array($optionName, $this->supportedOptions, true)) {
            $this->options[$optionName] = $optionValue;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function addOption(string $optionName, mixed $optionValue): void
    {
        if (\in_array($optionName, $this->supportedOptions, true)) {
            if (!isset($this->options[$optionName])) {
                $this->options[$optionName] = [];
            } elseif (!\is_array($this->options[$optionName])) {
                $this->options[$optionName] = [$this->options[$optionName]];
            }
            $this->options[$optionName][] = $optionValue;
        }
    }

    /**
     * @return null|mixed The set option or NULL if no option with given name found
     */
    public function getOption(string $optionName): mixed
    {
        $optionValue = null;

        if (isset($this->options[$optionName])) {
            $optionValue = $this->options[$optionName];
        }

        return $optionValue;
    }

    /**
     * @return string[] List of supported options
     */
    abstract protected function getSupportedOptions(): array;

    abstract protected function setDefaultOptions(): void;
}
