<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Manager\Style;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class StyleManagerTest extends TestCase
{
    public static function dataProviderForTestShouldApplyStyleOnEmptyCell(): array
    {
        return [
            // fillId, borderId, expected result
            [null, null, false],
            [0, null, false],
            [null, 0, false],
            [0, 0, false],
            [12, null, true],
            [null, 12, true],
            [12, 0, true],
            [0, 12, true],
            [12, 13, true],
        ];
    }

    #[DataProvider('dataProviderForTestShouldApplyStyleOnEmptyCell')]
    public function testShouldApplyStyleOnEmptyCell(?int $fillId, ?int $borderId, bool $expectedResult): void
    {
        $styleRegistryMock = $this->getMockBuilder(StyleRegistry::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFillIdForStyleId', 'getBorderIdForStyleId'])
            ->getMock()
        ;

        $styleRegistryMock
            ->method('getFillIdForStyleId')
            ->willReturn($fillId)
        ;

        $styleRegistryMock
            ->method('getBorderIdForStyleId')
            ->willReturn($borderId)
        ;

        $styleManager = new StyleManager($styleRegistryMock);
        $shouldApply = $styleManager->shouldApplyStyleOnEmptyCell(99);

        self::assertSame($expectedResult, $shouldApply);
    }
}
