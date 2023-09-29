<?php

declare(strict_types=1);

namespace Reader\XLSX\Helper;

use DateInterval;
use OpenSpout\Reader\XLSX\Helper\DateIntervalFormatHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DateIntervalFormatHelperTest extends TestCase
{
    /**
     * @return string[][]
     */
    public static function getExcelFormatsToPhpFormats(): array
    {
        return [
            ['[hh]', '%r%H'],
            ['[hh]:mm', '%r%H:%I'],
            ['[hh]:mm:ss', '%r%H:%I:%S'],
            ['[h]', '%r%h'],
            ['[h]:mm', '%r%h:%I'],
            ['[h]:mm:ss', '%r%h:%I:%S'],
            ['[mm]', '%r%I'],
            ['[mm]:ss', '%r%I:%S'],
            ['[m]', '%r%i'],
            ['[m]:ss', '%r%i:%S'],
            ['[ss]', '%r%S'],
            ['[s]', '%r%s'],
        ];
    }

    #[DataProvider('getExcelFormatsToPhpFormats')]
    public function testIsDurationFormatValid(string $excelDateFormat): void
    {
        self::assertTrue(DateIntervalFormatHelper::isDurationFormat($excelDateFormat));
    }

    public function testIsDurationFormatInvalid(): void
    {
        self::assertFalse(DateIntervalFormatHelper::isDurationFormat('[hh]:mm:ssx'));
        self::assertFalse(DateIntervalFormatHelper::isDurationFormat('x[hh]:mm:ss'));
    }

    #[DataProvider('getExcelFormatsToPhpFormats')]
    public function testToPHPDateFormat(string $excelDateFormat, string $expectedPHPDateFormat): void
    {
        $phpDateFormat = DateIntervalFormatHelper::toPHPDateIntervalFormat($excelDateFormat);
        self::assertSame($expectedPHPDateFormat, $phpDateFormat);
    }

    public static function dataProviderForTestCreateDateIntervalFromHours(): array
    {
        return [
            [0, '+00:00:00'],
            [1.5, '+36:00:00'],
            [-0.25, '-06:00:00'],
            [1 / 24, '+01:00:00'],
            [1 / 24 / 60, '+00:01:00'],
            [1 / 24 / 60 / 60, '+00:00:01'],
            [11 / 24 + 13 / 24 / 60 + 7 / 24 / 60 / 60, '+11:13:07'],
            [1.75 / 24, '+01:45:00'], // floor() vs. round() test on hours (infection)
            [1.75 / 24 / 60, '+00:01:45'], // floor() vs. round() test on minutes (infection)
            [1.3 / 24 / 60 / 60, '+00:00:01'], // round() vs. ceil() test on seconds (infection)
            [1.7 / 24 / 60 / 60, '+00:00:02'], // round() vs. ceil() test on seconds (infection)
            [59 / 24 / 60 + 59.9 / 24 / 60 / 60, '+01:00:00'], // rounding gain bubbling up
            [59.9 / 24 / 60 / 60, '+00:01:00'], // rounding gain bubbling up
        ];
    }

    #[DataProvider('dataProviderForTestCreateDateIntervalFromHours')]
    public function testCreateDateIntervalFromHours(float $dayFractions, string $expected): void
    {
        $interval = DateIntervalFormatHelper::createDateIntervalFromHours($dayFractions);
        self::assertSame($expected, $interval->format('%R%H:%I:%S'));
    }

    public function testFormatDateIntervalDoesNotMutateInterval(): void
    {
        $interval = new DateInterval('PT1H0M0S');
        $formatted = DateIntervalFormatHelper::formatDateInterval($interval, '[m]');
        self::assertSame('60', $formatted);
        self::assertSame(1, $interval->h);
        self::assertSame(0, $interval->i);
    }
}
