<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Validation\Rules;

use InvalidArgumentException;
use OpenSpout\Writer\XLSX\Validation\CellReference;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ListValidationRuleTest extends TestCase
{
    public function testSerializeWrapsInlineOptionsInQuotes(): void
    {
        $rule = new ListValidationRule(['Foo', 'Bar', 'Baz']);
        $serialized_validation_rule = $rule->serialize();

        self::assertSame('list', $serialized_validation_rule->type);
        self::assertSame('"Foo,Bar,Baz"', $serialized_validation_rule->formula1);
    }

    public function testSerializeEscapesXmlSpecialCharactersInInlineList(): void
    {
        $rule = new ListValidationRule(['<Active>', 'A&B', '"Quoted"']);
        $serialized_validation_rule = $rule->serialize();

        self::assertSame('"&lt;Active&gt;,A&amp;B,"Quoted""', $serialized_validation_rule->formula1);
    }

    public function testConstructorThrowsExceptionWhenOptionContainsComma(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('contains a comma, which is not allowed');

        new ListValidationRule(['Foo, with comma', 'Bar']);
    }

    public function testSerializeReturnsFormattedCellReference(): void
    {
        $cellRef = new CellReference(0, 1, 0, 10); // Represents A1:A10
        $rule = new ListValidationRule($cellRef);
        $serialized_validation_rule = $rule->serialize();

        self::assertSame('list', $serialized_validation_rule->type);
        self::assertSame('A1:A10', $serialized_validation_rule->formula1);
    }

    public function testSerializeWithSingleCellReference(): void
    {
        $cellRef = new CellReference(5, 5, 5, 5); // Represents F5:F5
        $rule = new ListValidationRule($cellRef);
        $serialized_validation_rule = $rule->serialize();

        self::assertSame('F5:F5', $serialized_validation_rule->formula1);
    }
}
