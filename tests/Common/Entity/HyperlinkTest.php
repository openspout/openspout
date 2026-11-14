<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity;

use OpenSpout\Common\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class HyperlinkTest extends TestCase
{
    public function testToUrl(): void
    {
        self::assertSame('https://example.com/?a=1&b=2', (string) Hyperlink::toUrl('https://example.com/?a=1&b=2'));
    }

    #[DataProvider('provideToCellCases')]
    public function testToCell(string $expected, string $cellReference, ?string $sheetName): void
    {
        self::assertSame($expected, (string) Hyperlink::toCell($cellReference, $sheetName));
    }

    /**
     * @return iterable<string, array{string, string, ?string}>
     */
    public static function provideToCellCases(): iterable
    {
        yield 'same sheet' => ['#A42', 'A42', null];

        yield 'other sheet' => ["#'Orders'!A42", 'A42', 'Orders'];

        yield 'sheet name with spaces' => ["#'Order Details'!A42", 'A42', 'Order Details'];

        yield 'sheet name with quote' => ["#'O''Brien'!B2", 'B2', "O'Brien"];

        yield 'range' => ["#'Orders'!B2:D10", 'B2:D10', 'Orders'];

        yield 'absolute reference' => ['#$A$1', '$A$1', null];

        yield 'lowercase' => ['#xfd1048576', 'xfd1048576', null];
    }

    public function testToSheet(): void
    {
        self::assertSame("#'Order Details'!A1", (string) Hyperlink::toSheet('Order Details'));
    }

    #[DataProvider('provideToCellShouldThrowOnInvalidCellReferenceCases')]
    public function testToCellShouldThrowOnInvalidCellReference(string $cellReference): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid cell reference: {$cellReference}");

        Hyperlink::toCell($cellReference, 'Orders');
    }

    /**
     * @return iterable<array{string}>
     */
    public static function provideToCellShouldThrowOnInvalidCellReferenceCases(): iterable
    {
        yield [''];

        yield ['A'];

        yield ['42'];

        yield ['ABCD1'];

        yield ['A1:'];

        yield ['Orders!A1'];

        yield [' A1'];

        yield ['A1 '];

        yield ["A1\n"];

        yield ['A1:B2:C3'];
    }
}
