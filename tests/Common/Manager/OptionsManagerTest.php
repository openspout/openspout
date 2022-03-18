<?php

declare(strict_types=1);

namespace OpenSpout\Common\Manager;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class OptionsManagerTest extends TestCase
{
    private OptionsManagerAbstract $optionsManager;

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
        self::assertSame('foo-val', $optionsManager->getOption('foo'));
        self::assertFalse($optionsManager->getOption('bar'));
    }

    public function testOptionsManagerShouldReturnUpdatedOptionValue(): void
    {
        $optionsManager = $this->optionsManager;
        $optionsManager->setOption('foo', 'new-val');
        self::assertSame('new-val', $optionsManager->getOption('foo'));
    }

    public function testOptionsManagerShouldReturnNullIfNoDefaultValueSet(): void
    {
        $optionsManager = $this->optionsManager;
        self::assertNull($optionsManager->getOption('baz'));
    }

    public function testOptionsManagerShouldReturnNullIfNoOptionNotSupported(): void
    {
        $optionsManager = $this->optionsManager;
        $optionsManager->setOption('not-supported', 'something');
        self::assertNull($optionsManager->getOption('not-supported'));
    }

    public function testOptionManagerShouldReturnArrayIfListOptionsAdded(): void
    {
        $optionsManager = $this->optionsManager;
        $optionsManager->addOption('baz', 'something');
        $optionsManager->addOption('baz', 'something-else');
        self::assertIsArray($optionsManager->getOption('baz'));
        self::assertCount(2, $optionsManager->getOption('baz'));
        self::assertEquals('something', $optionsManager->getOption('baz')[0]);
        self::assertEquals('something-else', $optionsManager->getOption('baz')[1]);
    }

    public function testOptionsManagerShouldReturnNullIfListOptionNotSupported(): void
    {
        $optionsManager = $this->optionsManager;
        $optionsManager->addOption('not-supported', 'something');
        self::assertNull($optionsManager->getOption('not-supported'));
    }
}
