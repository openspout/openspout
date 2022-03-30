<?php

declare(strict_types=1);

namespace OpenSpout\Reader\CSV;

use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SheetTest extends TestCase
{
    public function testReaderShouldReturnCorrectSheetInfos(): void
    {
        $sheet = $this->openFileAndReturnSheet('csv_standard.csv');

        self::assertSame('', $sheet->getName());
        self::assertSame(0, $sheet->getIndex());
        self::assertTrue($sheet->isActive());
    }

    private function openFileAndReturnSheet(string $fileName): Sheet
    {
        $resourcePath = TestUsingResource::getResourcePath($fileName);
        $reader = new Reader();
        $reader->open($resourcePath);

        $sheet = $reader->getSheetIterator()->current();

        $reader->close();

        return $sheet;
    }
}
