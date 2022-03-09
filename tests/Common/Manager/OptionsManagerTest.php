<?php

namespace OpenSpout\Common\Manager;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class OptionsManagerTest extends TestCase
{
    /**
     * @var OptionsManagerAbstract
     */
    protected $optionsManager;

    protected function setUp(): void
    {
        $this->optionsManager = new class() extends OptionsManagerAbstract {
            protected function getSupportedOptions()
            {
                return [
                    'foo',
                    'bar',
                    'baz',
                ];
            }

            protected function setDefaultOptions()
            {
                $this->setOption('foo', 'foo-val');
                $this->setOption('bar', false);
            }
        };
        parent::setUp();
    }

    public function testOptionsManagerShouldReturnDefaultOptionsIfNothingSet()
    {
        $optionsManager = $this->optionsManager;
        static::assertSame('foo-val', $optionsManager->getOption('foo'));
        static::assertFalse($optionsManager->getOption('bar'));
    }

    public function testOptionsManagerShouldReturnUpdatedOptionValue()
    {
        $optionsManager = $this->optionsManager;
        $optionsManager->setOption('foo', 'new-val');
        static::assertSame('new-val', $optionsManager->getOption('foo'));
    }

    public function testOptionsManagerShouldReturnNullIfNoDefaultValueSet()
    {
        $optionsManager = $this->optionsManager;
        static::assertNull($optionsManager->getOption('baz'));
    }

    public function testOptionsManagerShouldReturnNullIfNoOptionNotSupported()
    {
        $optionsManager = $this->optionsManager;
        $optionsManager->setOption('not-supported', 'something');
        static::assertNull($optionsManager->getOption('not-supported'));
    }
}
