<?php

declare(strict_types=1);

namespace Writer\XLSX\Helper;

use DateInterval;
use OpenSpout\Writer\XLSX\Helper\DateIntervalHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DateIntervalHelper::class)]
final class DateIntervalHelperTest extends TestCase
{
    #[DataProvider('provideDateIntervalToExcelCases')]
    public function testToExcel(DateInterval $dateTime, float $expected): void
    {
        self::assertEqualsWithDelta($expected, DateIntervalHelper::toExcel($dateTime), 1E-5);
    }

    /**
     * @return array<array-key, array<array-key, DateInterval|float|int>>
     */
    public static function provideDateIntervalToExcelCases(): array
    {
        return [
            [DateInterval::createFromDateString('1 year'), 365.25],
            [DateInterval::createFromDateString('2 years'), 730.5],
            [DateInterval::createFromDateString('1 month'), 30.437],
            [DateInterval::createFromDateString('2 months'), 60.874],
            [DateInterval::createFromDateString('1 day'), 1],
            [DateInterval::createFromDateString('2 days'), 2],
            [DateInterval::createFromDateString('1 hour'), 0.04166666666],
            [DateInterval::createFromDateString('2 hours'), 0.08333333333],
            [DateInterval::createFromDateString('1 minute'), 1 / 24 / 60],
            [DateInterval::createFromDateString('2 minutes'), 2 / 24 / 60],
            [DateInterval::createFromDateString('1 second'), 1 / 24 / 60 / 60],
            [DateInterval::createFromDateString('2 seconds'), 2 / 24 / 60 / 60],
            [new DateInterval('PT12H30M0S'), 0.5 + 30 / 24 / 60],
        ];
    }
}
