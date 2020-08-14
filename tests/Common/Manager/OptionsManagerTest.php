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

    /**
     * @return void
     */
    public function testOptionManagerShouldReturnArrayIfListOptionsAdded()
    {
        $optionsManager = $this->optionsManager;
        $optionsManager->addOption('bar', 'something');
        $optionsManager->addOption('bar', 'something-else');
        $this->assertIsArray($optionsManager->getOption('bar'));
        $this->assertCount(2, $optionsManager->getOption('bar'));
        $this->assertEquals('something', $optionsManager->getOption('bar')[0]);
        $this->assertEquals('something-else', $optionsManager->getOption('bar')[1]);
    }

    /**
     * @return void
     */
    public function testOptionsManagerShouldReturnNullIfListOptionNotSupported()
    {
        $optionsManager = $this->optionsManager;
        $optionsManager->addOption('not-supported', 'something');
        $this->assertNull($optionsManager->getOption('not-supported'));
    }
}
