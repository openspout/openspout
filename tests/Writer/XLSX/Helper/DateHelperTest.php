<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Helper;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DateHelper::class)]
final class DateHelperTest extends TestCase
{
    #[DataProvider('provideDateTimeToExcelCases')]
    public function testToExcel(DateTimeInterface $dateTime, float $expected): void
    {
        self::assertEqualsWithDelta($expected, DateHelper::toExcel($dateTime), 1E-5);
    }

    /**
     * @return array<array-key, array<array-key, DateTimeInterface|float|int>>
     */
    public static function provideDateTimeToExcelCases(): array
    {
        return [
            [new DateTimeImmutable('1900-01-01'), 1.0], // Excel 1900 base calendar date
            [new DateTimeImmutable('1900-02-28'), 59.0], // This and next test show gap for the mythical
            [new DateTimeImmutable('1900-03-01'), 61.0], // MS Excel 1900 Leap Year
            [new DateTimeImmutable('1901-12-14'), 714.0], // Unix Timestamp 32-bit Earliest Date
            [new DateTimeImmutable('1903-12-31'), 1461.0],
            [new DateTimeImmutable('1904-01-01'), 1462.0], // Excel 1904 Calendar Base Date
            [new DateTimeImmutable('1904-01-02'), 1463.0],
            [new DateTimeImmutable('1960-12-19'), 22269.0],
            [new DateTimeImmutable('1970-01-01'), 25569.0], // Unix Timestamp Base Date
            [new DateTimeImmutable('1982-12-07'), 30292.0],
            [new DateTimeImmutable('2008-06-12'), 39611.0],
            [new DateTimeImmutable('2038-01-19'), 50424.0], // Unix Timestamp 32-bit Latest Date
            [new DateTimeImmutable('1903-05-18 13:37:46'), 1234.56789],
            [new DateTimeImmutable('1933-10-18 16:17:37'), 12345.6789],
            [new DateTimeImmutable('2099-12-31'), 73050.0],

            [new DateTimeImmutable('@-2147472000'), 714], // PHP 32-bit Earliest Date 14-Dec-1901
            [new DateTimeImmutable('@-2082931200'), 1461], // 31-Dec-1903
            [new DateTimeImmutable('@-2082844800'), 1462], // Excel 1904 Calendar Base Date   01-Jan-1904
            [new DateTimeImmutable('@-2082758400'), 1463], // 02-Jan-1904
            [new DateTimeImmutable('@-285120000'), 22269], // 19-Dec-1960
            [new DateTimeImmutable('@0'), 25569], // PHP Base Date 01-Jan-1970
            [new DateTimeImmutable('@408067200'), 30292], // 07-Dec-1982
            [new DateTimeImmutable('@1213228800'), 39611], // 12-Jun-2008
            [new DateTimeImmutable('@2147472000'), 50424], // PHP 32-bit Latest Date 19-Jan-2038
            [new DateTimeImmutable('@-2102494934'), 1234.56789], // 18-May-1903 13:37:46
            [new DateTimeImmutable('@-1142494943'), 12345.6789], // 18-Oct-1933 16:17:37

            [new DateTimeImmutable('2022-03-22 13:00:00'), 44642.5416666667],
            [new DateTimeImmutable('2022-03-22 13:05:00'), 44642.5451388889],
            [new DateTimeImmutable('2022-03-22 13:10:00'), 44642.5486111111],
            [new DateTimeImmutable('2022-03-22 13:12:14'), 44642.5501620370],
        ];
    }
}
