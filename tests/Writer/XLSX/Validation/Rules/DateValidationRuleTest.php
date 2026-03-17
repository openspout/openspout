<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Validation\Rules;

use DateTimeImmutable;
use DateTimeZone;
use OpenSpout\Writer\XLSX\Validation\CellReference;
use OpenSpout\Writer\XLSX\Validation\ValidationOperator;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DateValidationRuleTest extends TestCase
{
    public function testDateIsConvertedToExcelSerialNumber(): void
    {
        $rule = new DateValidationRule(
            ValidationOperator::Between,
            new DateTimeImmutable('2020-03-04 00:00:00', new DateTimeZone('UTC')),
            new DateTimeImmutable('2024-12-31 00:00:00', new DateTimeZone('UTC')),
        );
        $serialized_validation_rule = $rule->serialize();

        self::assertSame('date', $serialized_validation_rule->type);
        self::assertSame('43894', $serialized_validation_rule->formula1);
        self::assertSame('45657', $serialized_validation_rule->formula2);
    }

    public function testDateWithCellReference(): void
    {
        $rule = new DateValidationRule(
            ValidationOperator::GreaterThan,
            new CellReference(0, 1, 0, 1),
        );
        $serialized_validation_rule = $rule->serialize();

        self::assertSame('date', $serialized_validation_rule->type);
        self::assertSame('A1:A1', $serialized_validation_rule->formula1);
    }
}
