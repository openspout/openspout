<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Manager\Style;

use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Helper\Escaper\XLSX as XLSXEscaper;
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

        $styleManager = new StyleManager($styleRegistryMock, new XLSXEscaper());
        $shouldApply = $styleManager->shouldApplyStyleOnEmptyCell(99);

        self::assertSame($expectedResult, $shouldApply);
    }

    public function testFormatCodeEscapeInSectionContent(): void
    {
        $registry = new StyleRegistry(new Style());

        $registry->registerStyle((new Style())->setId(1)->setFormat('"€"* #,##0.00_-'));

        $styleManager = new StyleManager($registry, new XLSXEscaper());
        $output = $styleManager->getStylesXMLFileContent();
        self::assertStringContainsString('formatCode="&quot;€&quot;* #,##0.00_-"', $output);
    }
}
