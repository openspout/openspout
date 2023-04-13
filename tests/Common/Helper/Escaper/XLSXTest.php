<?php

declare(strict_types=1);

namespace OpenSpout\Common\Helper\Escaper;

use OpenSpout\Common\Helper\Escaper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class XLSXTest extends TestCase
{
    public static function dataProviderForTestEscape(): array
    {
        return [
            ['test', 'test'],
            ['adam\'s "car"', 'adam&#039;s &quot;car&quot;'],
            ["\n", "\n"],
            ["\r", "\r"],
            ["\t", "\t"],
            [\chr(0), '_x0000_'],
            [\chr(4), '_x0004_'],
            ['_x0000_', '_x005F_x0000_'],
            [\chr(21), '_x0015_'],
            ['control '.\chr(21).' character', 'control _x0015_ character'],
            ['control\'s '.\chr(21).' "character"', 'control&#039;s _x0015_ &quot;character&quot;'],
        ];
    }

    #[DataProvider('dataProviderForTestEscape')]
    public function testEscape(string $stringToEscape, string $expectedEscapedString): void
    {
        $escaper = new Escaper\XLSX();
        $escapedString = $escaper->escape($stringToEscape);

        self::assertSame($expectedEscapedString, $escapedString, 'Incorrect escaped string');
    }

    public static function dataProviderForTestUnescape(): array
    {
        return [
            ['test', 'test'],
            ['adam&#039;s &quot;car&quot;', 'adam&#039;s &quot;car&quot;'],
            ["\n", "\n"],
            ["\r", "\r"],
            ["\t", "\t"],
            ['_x0000_', \chr(0)],
            ['_x0004_', \chr(4)],
            ['_x005F_x0000_', '_x0000_'],
            ['_x0015_', \chr(21)],
            ['control _x0015_ character', 'control '.\chr(21).' character'],
            ['control&#039;s _x0015_ &quot;character&quot;', 'control&#039;s '.\chr(21).' &quot;character&quot;'],
        ];
    }

    #[DataProvider('dataProviderForTestUnescape')]
    public function testUnescape(string $stringToUnescape, string $expectedUnescapedString): void
    {
        $escaper = new Escaper\XLSX();
        $unescapedString = $escaper->unescape($stringToUnescape);

        self::assertSame($expectedUnescapedString, $unescapedString, 'Incorrect escaped string');
    }
}
