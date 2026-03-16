<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Validation\RuleSerializer;

use DateTimeInterface;
use InvalidArgumentException;
use OpenSpout\Writer\Common\Helper\CellHelper;
use OpenSpout\Writer\XLSX\Helper\DateHelper;
use OpenSpout\Writer\XLSX\Validation\CellReference;
use OpenSpout\Writer\XLSX\Validation\DataValidationRuleInterface;
use OpenSpout\Writer\XLSX\Validation\Rules\CustomValidationRule;
use OpenSpout\Writer\XLSX\Validation\Rules\DateValidationRule;
use OpenSpout\Writer\XLSX\Validation\Rules\DecimalValidationRule;
use OpenSpout\Writer\XLSX\Validation\Rules\ListValidationRule;
use OpenSpout\Writer\XLSX\Validation\Rules\TextLengthValidationRule;
use OpenSpout\Writer\XLSX\Validation\Rules\TimeOfDay;
use OpenSpout\Writer\XLSX\Validation\Rules\TimeValidationRule;
use OpenSpout\Writer\XLSX\Validation\Rules\WholeNumberValidationRule;

final class ValidationRuleSerializer
{
    public static function serializeRule(DataValidationRuleInterface $rule): SerializedValidationRule
    {
        if ($rule instanceof WholeNumberValidationRule) {
            return self::serializeWholeNumberRule($rule);
        }
        if ($rule instanceof TextLengthValidationRule) {
            return self::serializeTextLengthRule($rule);
        }
        if ($rule instanceof DecimalValidationRule) {
            return self::serializeDecimalRule($rule);
        }
        if ($rule instanceof DateValidationRule) {
            return self::serializeDateRule($rule);
        }
        if ($rule instanceof TimeValidationRule) {
            return self::serializeTimeRule($rule);
        }
        if ($rule instanceof ListValidationRule) {
            return self::serializeListRule($rule);
        }
        if ($rule instanceof CustomValidationRule) {
            return self::serializeCustomRule($rule);
        }

        throw new InvalidArgumentException('Unsupported validation rule type.');
    }

    private static function serializeWholeNumberRule(WholeNumberValidationRule $rule): SerializedValidationRule
    {
        return new SerializedValidationRule(
            type: $rule->getValidationRuleType()->value,
            operator: $rule->getOperator()->value,
            formula1: self::intValue($rule->getValue1()),
            formula2: self::intValue($rule->getValue2()),
        );
    }

    private static function serializeTextLengthRule(TextLengthValidationRule $rule): SerializedValidationRule
    {
        return new SerializedValidationRule(
            type: $rule->getValidationRuleType()->value,
            operator: $rule->getOperator()->value,
            formula1: self::intValue($rule->getValue1()),
            formula2: self::intValue($rule->getValue2()),
        );
    }

    private static function serializeDecimalRule(DecimalValidationRule $rule): SerializedValidationRule
    {
        return new SerializedValidationRule(
            type: $rule->getValidationRuleType()->value,
            operator: $rule->getOperator()->value,
            formula1: self::decimalValue($rule->getValue1()),
            formula2: self::decimalValue($rule->getValue2()),
        );
    }

    private static function serializeDateRule(DateValidationRule $rule): SerializedValidationRule
    {
        return new SerializedValidationRule(
            type: $rule->getValidationRuleType()->value,
            operator: $rule->getOperator()->value,
            formula1: self::dateValue($rule->getValue1()),
            formula2: self::dateValue($rule->getValue2()),
        );
    }

    private static function serializeTimeRule(TimeValidationRule $rule): SerializedValidationRule
    {
        return new SerializedValidationRule(
            type: $rule->getValidationRuleType()->value,
            operator: $rule->getOperator()->value,
            formula1: self::timeValue($rule->getValue1()),
            formula2: self::timeValue($rule->getValue2()),
        );
    }

    private static function serializeListRule(ListValidationRule $rule): SerializedValidationRule
    {
        if ($rule->value instanceof CellReference) {
            return new SerializedValidationRule(
                type: $rule->getValidationRuleType()->value,
                operator: null,
                formula1: self::serializeCellReference($rule->value),
            );
        }

        return new SerializedValidationRule(
            type: $rule->getValidationRuleType()->value,
            operator: null,
            formula1: '"'.implode(',', array_map(
                static fn (string $o) => htmlspecialchars($o, ENT_XML1),
                $rule->value,
            )).'"',
        );
    }

    private static function serializeCustomRule(CustomValidationRule $rule): SerializedValidationRule
    {
        return new SerializedValidationRule(
            type: $rule->getValidationRuleType()->value,
            operator: null,
            formula1: htmlspecialchars($rule->formula, ENT_XML1),
        );
    }

    private static function intValue(CellReference|int|null $value): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof CellReference ? self::serializeCellReference($value) : (string) $value;
    }

    private static function decimalValue(CellReference|float|null $value): ?string
    {
        if (null === $value) {
            return null;
        }
        if ($value instanceof CellReference) {
            return self::serializeCellReference($value);
        }

        return rtrim(rtrim(number_format($value, 14, '.', ''), '0'), '.');
    }

    private static function dateValue(CellReference|DateTimeInterface|null $value): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof CellReference
            ? self::serializeCellReference($value)
            : (string) DateHelper::toExcel($value);
    }

    private static function timeValue(CellReference|TimeOfDay|null $value): ?string
    {
        if (null === $value) {
            return null;
        }

        return $value instanceof CellReference
            ? self::serializeCellReference($value)
            : (string) $value->toDayFraction();
    }

    private static function serializeCellReference(CellReference $cellReference): string
    {
        return \sprintf(
            '%s%s:%s%s',
            CellHelper::getColumnLettersFromColumnIndex($cellReference->topLeftColumn),
            $cellReference->topLeftRow,
            CellHelper::getColumnLettersFromColumnIndex($cellReference->bottomRightColumn),
            $cellReference->bottomRightRow,
        );
    }
}
