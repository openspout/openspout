<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Validation\RuleSerializer;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use OpenSpout\Writer\XLSX\Validation\CellReference;
use OpenSpout\Writer\XLSX\Validation\Rules\CustomValidationRule;
use OpenSpout\Writer\XLSX\Validation\Rules\DateValidationRule;
use OpenSpout\Writer\XLSX\Validation\Rules\DecimalValidationRule;
use OpenSpout\Writer\XLSX\Validation\Rules\ListValidationRule;
use OpenSpout\Writer\XLSX\Validation\Rules\TextLengthValidationRule;
use OpenSpout\Writer\XLSX\Validation\Rules\TimeOfDay;
use OpenSpout\Writer\XLSX\Validation\Rules\TimeValidationRule;
use OpenSpout\Writer\XLSX\Validation\Rules\WholeNumberValidationRule;
use OpenSpout\Writer\XLSX\Validation\ValidationOperator;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ValidationRuleSerializerTest extends TestCase
{
    public function testWholeNumberWithLiteralValues(): void
    {
        $result = ValidationRuleSerializer::serializeRule(
            new WholeNumberValidationRule(ValidationOperator::Between, 0, 100)
        );

        self::assertSame('whole', $result->type);
        self::assertSame(ValidationOperator::Between->value, $result->operator);
        self::assertSame('0', $result->formula1);
        self::assertSame('100', $result->formula2);
    }

    public function testWholeNumberWithCellReference(): void
    {
        $result = ValidationRuleSerializer::serializeRule(
            new WholeNumberValidationRule(
                ValidationOperator::Between,
                new CellReference(0, 1, 0, 1),
                new CellReference(1, 1, 1, 1),
            )
        );

        self::assertSame('whole', $result->type);
        self::assertSame('A1:A1', $result->formula1);
        self::assertSame('B1:B1', $result->formula2);
    }

    public function testWholeNumberWithoutSecondValue(): void
    {
        $result = ValidationRuleSerializer::serializeRule(
            new WholeNumberValidationRule(ValidationOperator::GreaterThan, 5)
        );

        self::assertSame('whole', $result->type);
        self::assertSame('5', $result->formula1);
        self::assertNull($result->formula2);
    }

    public function testWholeNumberBetweenWithoutSecondValueThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Operator 'between' requires a second value.");

        new WholeNumberValidationRule(ValidationOperator::Between, 5);
    }

    public function testTextLengthWithLiteralValues(): void
    {
        $result = ValidationRuleSerializer::serializeRule(
            new TextLengthValidationRule(ValidationOperator::Between, 5, 50)
        );

        self::assertSame('textLength', $result->type);
        self::assertSame(ValidationOperator::Between->value, $result->operator);
        self::assertSame('5', $result->formula1);
        self::assertSame('50', $result->formula2);
    }

    public function testTextLengthWithCellReference(): void
    {
        $result = ValidationRuleSerializer::serializeRule(
            new TextLengthValidationRule(
                ValidationOperator::LessThanOrEqual,
                new CellReference(0, 1, 0, 1),
            )
        );

        self::assertSame('textLength', $result->type);
        self::assertSame('A1:A1', $result->formula1);
        self::assertNull($result->formula2);
    }

    public function testDecimalWithLiteralValues(): void
    {
        $result = ValidationRuleSerializer::serializeRule(
            new DecimalValidationRule(ValidationOperator::Between, 0.5, 9.99)
        );

        self::assertSame('decimal', $result->type);
        self::assertSame('0.5', $result->formula1);
        self::assertSame('9.99', $result->formula2);
    }

    public function testDecimalHandlesFloatArithmeticNoise(): void
    {
        $result = ValidationRuleSerializer::serializeRule(
            new DecimalValidationRule(ValidationOperator::GreaterThan, 0.1 + 0.2)
        );

        self::assertSame('0.3', $result->formula1);
    }

    public function testDecimalWithCellReference(): void
    {
        $result = ValidationRuleSerializer::serializeRule(
            new DecimalValidationRule(
                ValidationOperator::Between,
                new CellReference(0, 1, 0, 1),
                new CellReference(0, 2, 0, 2),
            )
        );

        self::assertSame('decimal', $result->type);
        self::assertSame('A1:A1', $result->formula1);
        self::assertSame('A2:A2', $result->formula2);
    }

    public function testDateIsConvertedToExcelSerial(): void
    {
        $result = ValidationRuleSerializer::serializeRule(
            new DateValidationRule(
                ValidationOperator::GreaterThanOrEqual,
                new DateTimeImmutable('2024-01-01', new DateTimeZone('UTC')),
            )
        );

        self::assertSame('date', $result->type);
        self::assertIsNumeric($result->formula1);
        self::assertNull($result->formula2);
    }

    public function testDateBetweenWithTwoSerials(): void
    {
        $result = ValidationRuleSerializer::serializeRule(
            new DateValidationRule(
                ValidationOperator::Between,
                new DateTimeImmutable('2024-01-01', new DateTimeZone('UTC')),
                new DateTimeImmutable('2024-12-31', new DateTimeZone('UTC')),
            )
        );

        self::assertIsNumeric($result->formula1);
        self::assertIsNumeric($result->formula2);
        self::assertLessThan((float) $result->formula2, (float) $result->formula1);
    }

    public function testDateWithCellReference(): void
    {
        $result = ValidationRuleSerializer::serializeRule(
            new DateValidationRule(
                ValidationOperator::GreaterThan,
                new CellReference(0, 1, 0, 1),
            )
        );

        self::assertSame('date', $result->type);
        self::assertSame('A1:A1', $result->formula1);
    }

    public function testTimeIsConvertedToDayFraction(): void
    {
        $result = ValidationRuleSerializer::serializeRule(
            new TimeValidationRule(
                ValidationOperator::Between,
                new TimeOfDay(6, 0, 0),   // 0.25
                new TimeOfDay(12, 0, 0),  // 0.5
            )
        );

        self::assertSame('time', $result->type);
        self::assertSame('0.25', $result->formula1);
        self::assertSame('0.5', $result->formula2);
    }

    public function testTimeWithCellReference(): void
    {
        $result = ValidationRuleSerializer::serializeRule(
            new TimeValidationRule(
                ValidationOperator::GreaterThan,
                new CellReference(0, 1, 0, 1),
            )
        );

        self::assertSame('time', $result->type);
        self::assertSame('A1:A1', $result->formula1);
    }

    public function testInlineListOptions(): void
    {
        $result = ValidationRuleSerializer::serializeRule(
            new ListValidationRule(['Active', 'Inactive', 'Pending'])
        );

        self::assertSame('list', $result->type);
        self::assertNull($result->operator);
        self::assertSame('"Active,Inactive,Pending"', $result->formula1);
    }

    public function testInlineListEscapesXmlCharacters(): void
    {
        $result = ValidationRuleSerializer::serializeRule(
            new ListValidationRule(['A&B', 'C<D'])
        );

        self::assertSame('"A&amp;B,C&lt;D"', $result->formula1);
    }

    public function testInlineListWithCommaThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('contains a comma');

        ValidationRuleSerializer::serializeRule(
            new ListValidationRule(['Active', 'In,valid'])
        );
    }

    public function testEmptyListThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ListValidationRule([]);
    }

    public function testListWithCellReference(): void
    {
        $result = ValidationRuleSerializer::serializeRule(
            new ListValidationRule(new CellReference(0, 1, 0, 10))
        );

        self::assertSame('list', $result->type);
        self::assertSame('A1:A10', $result->formula1);
        self::assertNull($result->operator);
    }

    public function testCustomFormulaIsEscaped(): void
    {
        $result = ValidationRuleSerializer::serializeRule(
            new CustomValidationRule('A1>B1&C1')
        );

        self::assertSame('custom', $result->type);
        self::assertNull($result->operator);
        self::assertSame('A1&gt;B1&amp;C1', $result->formula1);
    }

    public function testCellReferenceInvertedCoordinatesThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Top-left cell must not be below or to the right of bottom-right cell.');

        new CellReference(1, 10, 0, 1);
    }

    public function testTimeOfDayInvalidHoursThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Hours must be between 0 and 23, got 24.');

        new TimeOfDay(24);
    }

    public function testTimeOfDayInvalidMinutesThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Minutes must be between 0 and 59, got 60.');

        new TimeOfDay(9, 60);
    }

    public function testTimeOfDayInvalidSecondsThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Seconds must be between 0 and 59, got 60.');

        new TimeOfDay(9, 0, 60);
    }
}
