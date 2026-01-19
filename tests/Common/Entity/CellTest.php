<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity;

use DateInterval;
use DateTimeImmutable;
use OpenSpout\Common\Entity\Cell\BooleanCell;
use OpenSpout\Common\Entity\Cell\DateIntervalCell;
use OpenSpout\Common\Entity\Cell\DateTimeCell;
use OpenSpout\Common\Entity\Cell\EmptyCell;
use OpenSpout\Common\Entity\Cell\ErrorCell;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Common\Entity\Cell\NumericCell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Comment\Comment;
use OpenSpout\Common\Entity\Style\Style;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CellTest extends TestCase
{
    public function testCellTypeNumeric(): void
    {
        self::assertInstanceOf(NumericCell::class, Cell::fromValue(0));
        self::assertInstanceOf(NumericCell::class, Cell::fromValue(1));
        self::assertInstanceOf(NumericCell::class, Cell::fromValue(10.2));
        self::assertInstanceOf(NumericCell::class, Cell::fromValue(10.10000000000000000000001));
        self::assertInstanceOf(NumericCell::class, Cell::fromValue(0x539));
        self::assertInstanceOf(NumericCell::class, Cell::fromValue(0o2471));
        self::assertInstanceOf(NumericCell::class, Cell::fromValue(0b10100111001));
        self::assertInstanceOf(NumericCell::class, Cell::fromValue(1337e0));
    }

    public function testCellTypeString(): void
    {
        self::assertInstanceOf(StringCell::class, Cell::fromValue('String!'));
    }

    public function testCellTypeEmptyString(): void
    {
        self::assertInstanceOf(EmptyCell::class, Cell::fromValue(''));
    }

    public function testCellTypeEmptyNull(): void
    {
        self::assertInstanceOf(EmptyCell::class, Cell::fromValue(null));
    }

    public function testCellTypeBool(): void
    {
        self::assertInstanceOf(BooleanCell::class, Cell::fromValue(true));
        self::assertInstanceOf(BooleanCell::class, Cell::fromValue(false));
    }

    public function testCellTypeDate(): void
    {
        self::assertInstanceOf(DateTimeCell::class, Cell::fromValue(new DateTimeImmutable()));
        self::assertInstanceOf(DateIntervalCell::class, Cell::fromValue(new DateInterval('P2Y4DT6H8M')));
    }

    public function testCellTypeFormula(): void
    {
        self::assertInstanceOf(FormulaCell::class, Cell::fromValue('=SUM(A1:A2)'));
    }

    public function testErroredCellValueShouldBeNull(): void
    {
        $cell = new ErrorCell('#DIV/0');
        self::assertNull($cell->getValue());
        self::assertSame('#DIV/0', $cell->getRawValue());
    }

    public function testApplyExtraStylesIfNeededShouldApplyWrapTextIfCellContainsNewLine(): void
    {
        $cell = Cell::fromValue("multi\nlines");

        self::assertTrue($cell->style->shouldWrapText);
    }

    public function testApplyExtraStylesIfNeededShouldReturnNullIfWrapTextNotNeeded(): void
    {
        $cell = Cell::fromValue('oneline');

        self::assertNull($cell->style?->shouldWrapText);
    }

    public function testApplyExtraStylesIfNeededShouldReturnNullIfWrapTextAlreadyApplied(): void
    {
        $style = new Style(shouldWrapText: true);

        $cell = Cell::fromValue("multi\nlines", $style);

        self::assertTrue($cell->style->shouldWrapText);
    }

    public function testStringCellWithValue(): void
    {
        $cell = new StringCell('original', null, null);
        $newCell = $cell->withValue('modified');

        self::assertSame('modified', $newCell->getValue());
        self::assertSame('original', $cell->getValue());
    }

    public function testStringCellWithStyle(): void
    {
        $style1 = new Style(fontBold: true);
        $style2 = new Style(fontItalic: true);
        $cell = new StringCell('test', $style1, null);
        $newCell = $cell->withStyle($style2);

        self::assertTrue($newCell->style->fontItalic);
        self::assertTrue($cell->style->fontBold);
    }

    public function testStringCellWithComment(): void
    {
        $comment1 = new Comment();
        $comment2 = new Comment(visible: true);
        $cell = new StringCell('test', null, $comment1);
        $newCell = $cell->withComment($comment2);

        self::assertTrue($newCell->comment->visible);
        self::assertFalse($cell->comment->visible);
    }

    public function testStringCellWithoutStyle(): void
    {
        $style = new Style(fontBold: true);
        $cell = new StringCell('test', $style, null);
        $newCell = $cell->withoutStyle();

        self::assertNull($newCell->style);
        self::assertNotNull($cell->style);
    }

    public function testStringCellWithoutComment(): void
    {
        $comment = new Comment();
        $cell = new StringCell('test', null, $comment);
        $newCell = $cell->withoutComment();

        self::assertNull($newCell->comment);
        self::assertNotNull($cell->comment);
    }

    public function testNumericCellWithValue(): void
    {
        $cell = new NumericCell(42, null, null);
        $newCell = $cell->withValue(100);

        self::assertSame(100, $newCell->getValue());
        self::assertSame(42, $cell->getValue());
    }

    public function testBooleanCellWithValue(): void
    {
        $cell = new BooleanCell(true, null, null);
        $newCell = $cell->withValue(false);

        self::assertFalse($newCell->getValue());
        self::assertTrue($cell->getValue());
    }

    public function testFormulaCellWithValue(): void
    {
        $cell = new FormulaCell('=SUM(A1:A2)', null, null, null);
        $newCell = $cell->withValue('=SUM(B1:B2)');

        self::assertSame('=SUM(B1:B2)', $newCell->getValue());
        self::assertSame('=SUM(A1:A2)', $cell->getValue());
    }

    public function testFormulaCellWithComputedValue(): void
    {
        $cell = new FormulaCell('=SUM(A1:A2)', 10, null, null);
        $newCell = $cell->withComputedValue(20);

        self::assertSame(20, $newCell->getComputedValue());
        self::assertSame(10, $cell->getComputedValue());
    }

    public function testEmptyCellWithValue(): void
    {
        $cell = new EmptyCell(null, null, null);
        $newCell = $cell->withValue('');

        self::assertSame('', $newCell->getValue());
        self::assertNull($cell->getValue());
    }

    public function testErrorCellWithRawValue(): void
    {
        $cell = new ErrorCell('#DIV/0', null, null);
        $newCell = $cell->withRawValue('#N/A');

        self::assertSame('#N/A', $newCell->getRawValue());
        self::assertSame('#DIV/0', $cell->getRawValue());
    }

    public function testDateTimeCellWithValue(): void
    {
        $date1 = new DateTimeImmutable('2023-01-01');
        $date2 = new DateTimeImmutable('2023-12-31');
        $cell = new DateTimeCell($date1, null, null);
        $newCell = $cell->withValue($date2);

        self::assertSame($date2, $newCell->getValue());
        self::assertSame($date1, $cell->getValue());
    }

    public function testDateIntervalCellWithValue(): void
    {
        $interval1 = new DateInterval('P1D');
        $interval2 = new DateInterval('P2D');
        $cell = new DateIntervalCell($interval1, null, null);
        $newCell = $cell->withValue($interval2);

        self::assertSame($interval2, $newCell->getValue());
        self::assertSame($interval1, $cell->getValue());
    }
}
