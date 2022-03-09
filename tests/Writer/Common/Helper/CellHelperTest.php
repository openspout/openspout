<?php

namespace OpenSpout\Writer\Common\Helper;

use PHPUnit\Framework\TestCase;

/**
 * Class CellHelperTest.
 *
 * @internal
 * @coversNothing
 */
final class CellHelperTest extends TestCase
{
    /**
     * @return array
     */
    public function dataProviderForTestGetColumnLettersFromColumnIndex()
    {
        return [
            [0, 'A'],
            [1, 'B'],
            [25, 'Z'],
            [26, 'AA'],
            [28, 'AC'],
        ];
    }

    /**
     * @dataProvider dataProviderForTestGetColumnLettersFromColumnIndex
     *
     * @param int    $columnIndex
     * @param string $expectedColumnLetters
     */
    public function testGetColumnLettersFromColumnIndex($columnIndex, $expectedColumnLetters)
    {
        static::assertSame($expectedColumnLetters, CellHelper::getColumnLettersFromColumnIndex($columnIndex));
    }
}
