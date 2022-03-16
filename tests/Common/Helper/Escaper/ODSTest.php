<?php

namespace OpenSpout\Common\Helper\Escaper;

use OpenSpout\Common\Helper\Escaper;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ODSTest extends TestCase
{
    public function dataProviderForTestEscape(): array
    {
        return [
            ['test', 'test'],
            ['carl\'s "pokemon"', 'carl&#039;s &quot;pokemon&quot;'],
            ["\n", "\n"],
            ["\r", "\r"],
            ["\t", "\t"],
            ["\v", '�'],
            ["\f", '�'],
        ];
    }

    /**
     * @dataProvider dataProviderForTestEscape
     */
    public function testEscape(string $stringToEscape, string $expectedEscapedString): void
    {
        $escaper = new Escaper\ODS();
        $escapedString = $escaper->escape($stringToEscape);

        static::assertSame($expectedEscapedString, $escapedString, 'Incorrect escaped string');
    }
}
