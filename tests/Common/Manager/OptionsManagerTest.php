<?php

namespace OpenSpout\Common\Manager;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class OptionsManagerTest extends TestCase
{
    protected OptionsManagerAbstract $optionsManager;

    protected function setUp(): void
    {
        $this->optionsManager = new class() extends OptionsManagerAbstract {
            protected function getSupportedOptions(): array
            {
                return [
                    'foo',
                    'bar',
                    'baz',
                ];
            }

            protected function setDefaultOptions(): void
            {
                $this->setOption('foo', 'foo-val');
                $this->setOption('bar', false);
            }
        };
        parent::setUp();
    }

    public function testOptionsManagerShouldReturnDefaultOptionsIfNothingSet(): void
    {
        $optionsManager = $this->optionsManager;
        static::assertSame('foo-val', $optionsManager->getOption('foo'));
        static::assertFalse($optionsManager->getOption('bar'));
    }

    public function testOptionsManagerShouldReturnUpdatedOptionValue(): void
    {
        $optionsManager = $this->optionsManager;
        $optionsManager->setOption('foo', 'new-val');
        static::assertSame('new-val', $optionsManager->getOption('foo'));
    }

    public function testOptionsManagerShouldReturnNullIfNoDefaultValueSet(): void
    {
        $optionsManager = $this->optionsManager;
        static::assertNull($optionsManager->getOption('baz'));
    }

    public function testOptionsManagerShouldReturnNullIfNoOptionNotSupported(): void
    {
        $optionsManager = $this->optionsManager;
        $optionsManager->setOption('not-supported', 'something');
        static::assertNull($optionsManager->getOption('not-supported'));
    }

    public function testOptionManagerShouldReturnArrayIfListOptionsAdded(): void
    {
        $optionsManager = $this->optionsManager;
        $optionsManager->addOption('baz', 'something');
        $optionsManager->addOption('baz', 'something-else');
        static::assertIsArray($optionsManager->getOption('baz'));
        static::assertCount(2, $optionsManager->getOption('baz'));
        static::assertEquals('something', $optionsManager->getOption('baz')[0]);
        static::assertEquals('something-else', $optionsManager->getOption('baz')[1]);
    }

    public function testOptionsManagerShouldReturnNullIfListOptionNotSupported(): void
    {
        $optionsManager = $this->optionsManager;
        $optionsManager->addOption('not-supported', 'something');
        static::assertNull($optionsManager->getOption('not-supported'));
    }
}
