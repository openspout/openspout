<?php

declare(strict_types=1);

namespace OpenSpout\Common\Helper\Escaper;

use OpenSpout\Common\Helper\Escaper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ODSTest extends TestCase
{
    public static function dataProviderForTestEscape(): array
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

    #[DataProvider('dataProviderForTestEscape')]
    public function testEscape(string $stringToEscape, string $expectedEscapedString): void
    {
        $escaper = new Escaper\ODS();
        $escapedString = $escaper->escape($stringToEscape);

        self::assertSame($expectedEscapedString, $escapedString, 'Incorrect escaped string');
    }
}
