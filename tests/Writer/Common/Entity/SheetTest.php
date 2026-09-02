<?php

declare(strict_types=1);

namespace OpenSpout\Writer\Common\Entity;

use OpenSpout\Common\Helper\StringHelper;
use OpenSpout\Writer\Common\Manager\SheetManager;
use OpenSpout\Writer\Exception\InvalidSheetNameException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SheetTest extends TestCase
{
    private SheetManager $sheetManager;

    protected function setUp(): void
    {
        $this->sheetManager = new SheetManager(StringHelper::factory());
    }

    public function testGetSheetName(): void
    {
        $sheets = [$this->createSheet(0, 'workbookId1'), $this->createSheet(1, 'workbookId1')];

        self::assertSame('Sheet1', $sheets[0]->getName(), 'Invalid name for the first sheet');
        self::assertSame('Sheet2', $sheets[1]->getName(), 'Invalid name for the second sheet');
    }

    public function testSetSheetNameShouldCreateSheetWithCustomName(): void
    {
        $customSheetName = 'CustomName';
        $sheet = $this->createSheet(0, 'workbookId1');
        $sheet->setName($customSheetName);

        self::assertSame($customSheetName, $sheet->getName(), "The sheet name should have been changed to '{$customSheetName}'");
    }

    #[DataProvider('provideSetSheetNameShouldThrowOnInvalidNameCases')]
    public function testSetSheetNameShouldThrowOnInvalidName(string $customSheetName): void
    {
        $sheet = $this->createSheet(0, 'workbookId1');

        $this->expectException(InvalidSheetNameException::class);
        $sheet->setName($customSheetName);
    }

    public static function provideSetSheetNameShouldThrowOnInvalidNameCases(): iterable
    {
        return [
            [''],
            ['this title exceeds the 31 characters limit'],
            ['Illegal \\'],
            ['Illegal /'],
            ['Illegal ?'],
            ['Illegal *'],
            ['Illegal :'],
            ['Illegal ['],
            ['Illegal ]'],
            ['\'Illegal start'],
            ['Illegal end\''],
            ['History'],
            ['HISTORY'],
            ['history'],
        ];
    }

    public function testSetSheetNameShouldNotThrowWhenSettingSameNameAsCurrentOne(): void
    {
        $customSheetName = 'Sheet name';
        $sheet = $this->createSheet(0, 'workbookId1');
        $sheet->setName($customSheetName);
        $sheet->setName($customSheetName);
        $this->expectNotToPerformAssertions();
    }

    public function testSetSheetNameShouldThrowWhenNameIsAlreadyUsed(): void
    {
        $this->expectException(InvalidSheetNameException::class);

        $customSheetName = 'Sheet name';

        $sheet = $this->createSheet(0, 'workbookId1');
        $sheet->setName($customSheetName);

        $sheet = $this->createSheet(1, 'workbookId1');
        $sheet->setName($customSheetName);
    }

    public function testSetSheetNameShouldNotThrowWhenSameNameUsedInDifferentWorkbooks(): void
    {
        $customSheetName = 'Sheet name';

        $sheet = $this->createSheet(0, 'workbookId1');
        $sheet->setName($customSheetName);

        $sheet = $this->createSheet(0, 'workbookId2');
        $sheet->setName($customSheetName);

        $sheet = $this->createSheet(1, 'workbookId3');
        $sheet->setName($customSheetName);
        $this->expectNotToPerformAssertions();
    }

    public function testSetColumnHiddenGathersConsecutiveColumnsIntoRanges(): void
    {
        $sheet = $this->createSheet(0, 'workbookId1');
        $sheet->setColumnHidden(true, 1, 2, 4);

        $hiddens = $sheet->getColumnHiddens();
        self::assertCount(2, $hiddens);
        self::assertSame([1, 2, true], [$hiddens[0]->start, $hiddens[0]->end, $hiddens[0]->hidden]);
        self::assertSame([4, 4, true], [$hiddens[1]->start, $hiddens[1]->end, $hiddens[1]->hidden]);
    }

    public function testSetColumnHiddenForRangeAppendsEntry(): void
    {
        $sheet = $this->createSheet(0, 'workbookId1');
        $sheet->setColumnHiddenForRange(true, 2, 5);

        $hiddens = $sheet->getColumnHiddens();
        self::assertCount(1, $hiddens);
        self::assertSame(2, $hiddens[0]->start);
        self::assertSame(5, $hiddens[0]->end);
        self::assertTrue($hiddens[0]->hidden);
    }

    public function testSetColumnCollapsedForRangeAppendsEntry(): void
    {
        $sheet = $this->createSheet(0, 'workbookId1');
        $sheet->setColumnCollapsedForRange(true, 2, 5);

        $collapseds = $sheet->getColumnCollapseds();
        self::assertCount(1, $collapseds);
        self::assertSame(2, $collapseds[0]->start);
        self::assertSame(5, $collapseds[0]->end);
        self::assertTrue($collapseds[0]->collapsed);
    }

    public function testSetColumnOutlineLevelAppendsEntry(): void
    {
        $sheet = $this->createSheet(0, 'workbookId1');
        $sheet->setColumnOutlineLevel(3, 1);

        $levels = $sheet->getColumnOutlineLevels();
        self::assertCount(1, $levels);
        self::assertSame(1, $levels[0]->start);
        self::assertSame(1, $levels[0]->end);
        self::assertSame(3, $levels[0]->level);
    }

    public function testSetColumnOutlineLevelForRangeAppendsEntry(): void
    {
        $sheet = $this->createSheet(0, 'workbookId1');
        $sheet->setColumnOutlineLevelForRange(3, 2, 5);

        $levels = $sheet->getColumnOutlineLevels();
        self::assertCount(1, $levels);
        self::assertSame(2, $levels[0]->start);
        self::assertSame(5, $levels[0]->end);
        self::assertSame(3, $levels[0]->level);
    }

    /**
     * @param 0|positive-int $sheetIndex
     */
    private function createSheet(int $sheetIndex, string $associatedWorkbookId): Sheet
    {
        return new Sheet($sheetIndex, $associatedWorkbookId, $this->sheetManager);
    }
}
