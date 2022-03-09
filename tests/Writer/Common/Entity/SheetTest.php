<?php

namespace OpenSpout\Writer\Common\Entity;

use OpenSpout\Common\Helper\StringHelper;
use OpenSpout\Writer\Common\Manager\SheetManager;
use OpenSpout\Writer\Exception\InvalidSheetNameException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SheetTest extends TestCase
{
    /** @var SheetManager */
    private $sheetManager;

    protected function setUp(): void
    {
        $this->sheetManager = new SheetManager(new StringHelper());
    }

    public function testGetSheetName()
    {
        $sheets = [$this->createSheet(0, 'workbookId1'), $this->createSheet(1, 'workbookId1')];

        static::assertSame('Sheet1', $sheets[0]->getName(), 'Invalid name for the first sheet');
        static::assertSame('Sheet2', $sheets[1]->getName(), 'Invalid name for the second sheet');
    }

    public function testSetSheetNameShouldCreateSheetWithCustomName()
    {
        $customSheetName = 'CustomName';
        $sheet = $this->createSheet(0, 'workbookId1');
        $sheet->setName($customSheetName);

        static::assertSame($customSheetName, $sheet->getName(), "The sheet name should have been changed to '{$customSheetName}'");
    }

    /**
     * @return array
     */
    public function dataProviderForInvalidSheetNames()
    {
        return [
            [null],
            [21],
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
        ];
    }

    /**
     * @dataProvider dataProviderForInvalidSheetNames
     *
     * @param string $customSheetName
     */
    public function testSetSheetNameShouldThrowOnInvalidName($customSheetName)
    {
        $this->expectException(InvalidSheetNameException::class);

        $sheet = $this->createSheet(0, 'workbookId1');
        $sheet->setName($customSheetName);
    }

    public function testSetSheetNameShouldNotThrowWhenSettingSameNameAsCurrentOne()
    {
        $customSheetName = 'Sheet name';
        $sheet = $this->createSheet(0, 'workbookId1');
        $sheet->setName($customSheetName);
        $sheet->setName($customSheetName);
        $this->expectNotToPerformAssertions();
    }

    public function testSetSheetNameShouldThrowWhenNameIsAlreadyUsed()
    {
        $this->expectException(InvalidSheetNameException::class);

        $customSheetName = 'Sheet name';

        $sheet = $this->createSheet(0, 'workbookId1');
        $sheet->setName($customSheetName);

        $sheet = $this->createSheet(1, 'workbookId1');
        $sheet->setName($customSheetName);
    }

    public function testSetSheetNameShouldNotThrowWhenSameNameUsedInDifferentWorkbooks()
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

    /**
     * @param int $sheetIndex
     * @param int $associatedWorkbookId
     *
     * @return Sheet
     */
    private function createSheet($sheetIndex, $associatedWorkbookId)
    {
        return new Sheet($sheetIndex, $associatedWorkbookId, $this->sheetManager);
    }
}
