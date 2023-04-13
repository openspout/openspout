<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX\Helper;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DateFormatHelperTest extends TestCase
{
    public static function dataProviderForTestToPHPDateFormat(): array
    {
        return [
            // Excel date format, expected PHP date format
            ['m/d/yy hh:mm', 'n/j/y H:i'],
            ['mmm-yy', 'M-y'],
            ['d-mmm-yy', 'j-M-y'],
            ['m/dd/yyyy', 'n/d/Y'],
            ['e mmmmm dddd', 'Y M l'],
            ['MMMM DDD', 'F D'],
            ['hh:mm:ss.s', 'H:i:s'],
            ['h:mm:ss AM/PM', 'g:i:s A'],
            ['hh:mm AM/PM', 'h:i A'],
            ['[$-409]hh:mm AM/PM', 'h:i A'],
            ['[$USD-F480]hh:mm AM/PM', 'h:i A'],
            ['"Day " d', '\\D\\a\\y\\  j'],
            ['yy "Year" m "Month"', 'y \\Y\\e\\a\\r n \\M\\o\\n\\t\\h'],
            ['mmm-yy;@', 'M-y'],
            ['[$-409]hh:mm AM/PM;"foo"@', 'h:i A'],
        ];
    }

    #[DataProvider('dataProviderForTestToPHPDateFormat')]
    public function testToPHPDateFormat(string $excelDateFormat, string $expectedPHPDateFormat): void
    {
        $phpDateFormat = DateFormatHelper::toPHPDateFormat($excelDateFormat);
        self::assertSame($expectedPHPDateFormat, $phpDateFormat);
    }
}
