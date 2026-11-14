<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity;

use OpenSpout\Common\Exception\InvalidArgumentException;
use Stringable;

/**
 * Target of a cell hyperlink: an external URL, or a cell or sheet within the workbook.
 *
 * Links within the workbook are represented as "#" followed by the location,
 * e.g. "#'Order Details'!A42", the same format as used by Excel's HYPERLINK() function.
 */
final readonly class Hyperlink implements Stringable
{
    private function __construct(
        private string $target,
    ) {}

    public function __toString(): string
    {
        return $this->target;
    }

    public static function toUrl(string $url): self
    {
        return new self($url);
    }

    /**
     * @param string      $cellReference Cell or range to link to, e.g. "A42" or "B2:D10"
     * @param null|string $sheetName     Sheet to link to, null for the sheet of the linking cell
     *
     * @throws InvalidArgumentException If the cell reference is invalid
     */
    public static function toCell(string $cellReference, ?string $sheetName = null): self
    {
        if (1 !== preg_match('/^\$?[A-Z]{1,3}\$?\d+(:\$?[A-Z]{1,3}\$?\d+)?$/iD', $cellReference)) {
            throw new InvalidArgumentException("Invalid cell reference: {$cellReference}");
        }

        if (null === $sheetName) {
            return new self("#{$cellReference}");
        }

        // quoting is always valid, a quote inside the name is escaped by doubling it
        $escapedSheetName = str_replace("'", "''", $sheetName);

        return new self("#'{$escapedSheetName}'!{$cellReference}");
    }

    public static function toSheet(string $sheetName): self
    {
        return self::toCell('A1', $sheetName);
    }
}
