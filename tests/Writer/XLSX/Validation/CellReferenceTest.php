<?php

declare(strict_types=1);

namespace Writer\XLSX\Validation;

use InvalidArgumentException;
use OpenSpout\Writer\XLSX\Validation\CellReference;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CellReferenceTest extends TestCase
{
    public function testAbsoluteSingleCell(): void
    {
        $ref = new CellReference(0, 1, 0, 1);

        self::assertSame('$A$1:$A$1', $ref->serialize());
    }

    public function testAbsoluteRange(): void
    {
        $ref = new CellReference(0, 1, 2, 10);

        self::assertSame('$A$1:$C$10', $ref->serialize());
    }

    public function testRelativeSingleCell(): void
    {
        $ref = new CellReference(0, 1, 0, 1, absolute: false);

        self::assertSame('A1:A1', $ref->serialize());
    }

    public function testRelativeRange(): void
    {
        $ref = new CellReference(0, 1, 2, 10, absolute: false);

        self::assertSame('A1:C10', $ref->serialize());
    }

    public function testAbsoluteWithSheetName(): void
    {
        $ref = new CellReference(0, 1, 0, 10, sheetName: 'Sheet1');

        self::assertSame('Sheet1!$A$1:$A$10', $ref->serialize());
    }

    public function testRelativeWithSheetName(): void
    {
        $ref = new CellReference(0, 1, 0, 10, absolute: false, sheetName: 'Sheet1');

        self::assertSame('Sheet1!A1:A10', $ref->serialize());
    }

    public function testSheetNameWithSpacesIsQuoted(): void
    {
        $ref = new CellReference(0, 1, 0, 10, sheetName: 'Lookup Data');

        self::assertSame("'Lookup Data'!\$A\$1:\$A\$10", $ref->serialize());
    }

    public function testMultiLetterColumn(): void
    {
        $ref = new CellReference(26, 1, 26, 1);

        self::assertSame('$AA$1:$AA$1', $ref->serialize());
    }

    public function testInvertedRowsThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CellReference(0, 10, 0, 1);
    }

    public function testInvertedColumnsThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CellReference(5, 1, 2, 10);
    }
}
