<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX\Manager;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class StyleManagerTest extends TestCase
{
    public function testShouldFormatNumericValueAsDateWithDefaultStyle(): void
    {
        $styleManager = $this->getStyleManagerMock();
        $shouldFormatAsDate = $styleManager->shouldFormatNumericValueAsDate(0);
        self::assertFalse($shouldFormatAsDate);
    }

    public function testShouldFormatNumericValueAsDateWhenShouldNotApplyNumberFormat(): void
    {
        $styleManager = $this->getStyleManagerMock([[], ['applyNumberFormat' => false, 'numFmtId' => 14]]);
        $shouldFormatAsDate = $styleManager->shouldFormatNumericValueAsDate(1);
        self::assertFalse($shouldFormatAsDate);
    }

    public function testShouldFormatNumericValueAsDateWithGeneralFormat(): void
    {
        $styleManager = $this->getStyleManagerMock([[], ['applyNumberFormat' => true, 'numFmtId' => 0]]);
        $shouldFormatAsDate = $styleManager->shouldFormatNumericValueAsDate(1);
        self::assertFalse($shouldFormatAsDate);
    }

    public function testShouldFormatNumericValueAsDateWithNonDateBuiltinFormat(): void
    {
        $styleManager = $this->getStyleManagerMock([[], ['applyNumberFormat' => true, 'numFmtId' => 9]]);
        $shouldFormatAsDate = $styleManager->shouldFormatNumericValueAsDate(1);
        self::assertFalse($shouldFormatAsDate);
    }

    public function testShouldFormatNumericValueAsDateWithNoNumFmtId(): void
    {
        $styleManager = $this->getStyleManagerMock([[], ['applyNumberFormat' => true, 'numFmtId' => null]]);
        $shouldFormatAsDate = $styleManager->shouldFormatNumericValueAsDate(1);
        self::assertFalse($shouldFormatAsDate);
    }

    public function testShouldFormatNumericValueAsDateWithBuiltinDateFormats(): void
    {
        $builtinNumFmtIdsForDate = [14, 15, 16, 17, 18, 19, 20, 21, 22, 45, 46, 47];

        foreach ($builtinNumFmtIdsForDate as $builtinNumFmtIdForDate) {
            $styleManager = $this->getStyleManagerMock([[], ['applyNumberFormat' => true, 'numFmtId' => $builtinNumFmtIdForDate]]);
            $shouldFormatAsDate = $styleManager->shouldFormatNumericValueAsDate(1);

            self::assertTrue($shouldFormatAsDate);
        }
    }

    public function testShouldFormatNumericValueAsDateWhenApplyNumberFormatNotSetAndUsingBuiltinDateFormat(): void
    {
        $styleManager = $this->getStyleManagerMock([[], ['applyNumberFormat' => null, 'numFmtId' => 14]]);
        $shouldFormatAsDate = $styleManager->shouldFormatNumericValueAsDate(1);

        self::assertTrue($shouldFormatAsDate);
    }

    public function testShouldFormatNumericValueAsDateWhenApplyNumberFormatNotSetAndUsingBuiltinNonDateFormat(): void
    {
        $styleManager = $this->getStyleManagerMock([[], ['applyNumberFormat' => null, 'numFmtId' => 9]]);
        $shouldFormatAsDate = $styleManager->shouldFormatNumericValueAsDate(1);

        self::assertFalse($shouldFormatAsDate);
    }

    public function testShouldFormatNumericValueAsDateWhenCustomNumberFormatNotFound(): void
    {
        $styleManager = $this->getStyleManagerMock([[], ['applyNumberFormat' => true, 'numFmtId' => 165]], [166 => []]);
        $shouldFormatAsDate = $styleManager->shouldFormatNumericValueAsDate(1);

        self::assertFalse($shouldFormatAsDate);
    }

    public static function dataProviderForCustomDateFormats(): array
    {
        return [
            // number format, expectedResult
            ['[$-409]dddd\,\ mmmm\ d\,\ yy', true],
            ['[$-409]d\-mmm\-yy;@', true],
            ['[$-409]d\-mmm\-yyyy;@', true],
            ['mm/dd/yy;@', true],
            ['MM/DD/YY;@', true],
            ['[$-F800]dddd\,\ mmmm\ dd\,\ yyyy', true],
            ['m/d;@', true],
            ['m/d/yy;@', true],
            ['[$-409]d\-mmm;@', true],
            ['[$-409]dd\-mmm\-yy;@', true],
            ['[$-409]mmm\-yy;@', true],
            ['[$-409]mmmm\-yy;@', true],
            ['[$-409]mmmm\ d\,\ yyyy;@', true],
            ['[$-409]m/d/yy\ h:mm\ AM/PM;@', true],
            ['m/d/yy\ h:mm;@', true],
            ['[$-409]mmmmm;@', true],
            ['[$-409]MMmmM;@', true],
            ['[$-409]mmmmm\-yy;@', true],
            ['m/d/yyyy;@', true],
            ['[$-409]m/d/yy\--h:mm;@', true],
            ['General', false],
            ['GENERAL', false],
            ['\ma\yb\e', false],
            ['[Red]foo;', false],
            ['foo "mm/dd/yy"', false],
        ];
    }

    #[DataProvider('dataProviderForCustomDateFormats')]
    public function testShouldFormatNumericValueAsDateWithCustomDateFormats(string $numberFormat, bool $expectedResult): void
    {
        $numFmtId = 165;
        $styleManager = $this->getStyleManagerMock([[], ['applyNumberFormat' => true, 'numFmtId' => $numFmtId]], [$numFmtId => $numberFormat]);
        $shouldFormatAsDate = $styleManager->shouldFormatNumericValueAsDate(1);

        self::assertSame($expectedResult, $shouldFormatAsDate);
    }

    private function getStyleManagerMock(array $styleAttributes = [], array $customNumberFormats = []): StyleManager
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|StyleManager $styleManager */
        $styleManager = $this->getMockBuilder(StyleManager::class)
            ->setConstructorArgs(['/path/to/file.xlsx', uniqid()])
            ->onlyMethods(['getCustomNumberFormats', 'getStylesAttributes'])
            ->getMock()
        ;

        $styleManager->method('getStylesAttributes')->willReturn($styleAttributes);
        $styleManager->method('getCustomNumberFormats')->willReturn($customNumberFormats);

        return $styleManager;
    }
}
