<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common\Helper;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CellHelperTest extends TestCase
{
    public static function dataProviderForTestGetColumnLettersFromColumnIndex(): array
    {
        return [
            [0, 'A'],
            [1, 'B'],
            [25, 'Z'],
            [26, 'AA'],
            [28, 'AC'],
        ];
    }

    #[DataProvider('dataProviderForTestGetColumnLettersFromColumnIndex')]
    public function testGetColumnLettersFromColumnIndex(int $columnIndex, string $expectedColumnLetters): void
    {
        self::assertSame($expectedColumnLetters, CellHelper::getColumnLettersFromColumnIndex($columnIndex));
    }
}
