<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity;

use DateInterval;
use DateTimeInterface;
use InvalidArgumentException;
use OpenSpout\Common\Entity\Comment\TextRun;
use OpenSpout\Common\Entity\Style\Style;

final readonly class Row
{
    public const float DEFAULT_HEIGHT = 0;

    /**
     * @param array<non-negative-int, Cell> $cells
     */
    public function __construct(
        public array $cells,
        public float $height = self::DEFAULT_HEIGHT,
    ) {
        foreach ($this->cells as $index => $cell) {
            if (!\is_int($index) || 0 > $index) {
                throw new InvalidArgumentException(\sprintf(
                    'Cell indexes must be non-negative integers, %s',
                    $index
                ));
            }
            if (!$cell instanceof Cell) {
                throw new InvalidArgumentException(\sprintf(
                    'Cells must be instance of %s, %s provided at index %s',
                    Cell::class,
                    get_debug_type($cell),
                    $index
                ));
            }
        }
    }

    /**
     * @param array<non-negative-int, Cell> $cells
     */
    public function withCells(array $cells): self
    {
        return new self($cells, $this->height);
    }

    public function withHeight(float $height): self
    {
        return new self($this->cells, $height);
    }

    /**
     * @return non-negative-int
     */
    public function getNumCells(): int
    {
        // When using "setCellAtIndex", it's possible to
        // have "$this->cells" contain holes.
        if ([] === $this->cells) {
            return 0;
        }

        return max(array_keys($this->cells)) + 1;
    }

    /**
     * @return array<non-negative-int, null|bool|DateInterval|DateTimeInterface|float|int|string|TextRun[]> The row values, as array
     */
    public function toArray(): array
    {
        return array_map(static function (Cell $cell): array|bool|DateInterval|DateTimeInterface|float|int|string|null {
            return $cell->getValue();
        }, $this->cells);
    }

    public function isEmpty(): bool
    {
        foreach ($this->cells as $cell) {
            if (!$cell instanceof Cell\EmptyCell) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<array-key, null|bool|DateInterval|DateTimeInterface|float|int|string|TextRun[]> $cellValues
     */
    public static function fromValues(array $cellValues, float $height = self::DEFAULT_HEIGHT): self
    {
        $cells = array_map(static function (array|bool|DateInterval|DateTimeInterface|float|int|string|null $cellValue): Cell {
            return Cell::fromValue($cellValue);
        }, $cellValues);

        return new self(array_values($cells), $height);
    }

    /**
     * @param array<array-key, null|bool|DateInterval|DateTimeInterface|float|int|string|TextRun[]> $cellValues
     * @param array<array-key, Style>                                                               $columnStyles
     */
    public static function fromValuesWithStyles(array $cellValues, array $columnStyles, float $height = self::DEFAULT_HEIGHT): self
    {
        $cells = array_map(static function (array|bool|DateInterval|DateTimeInterface|float|int|string|null $cellValue, int|string $key) use ($columnStyles): Cell {
            return Cell::fromValue($cellValue, $columnStyles[$key] ?? null);
        }, $cellValues, array_keys($cellValues));

        return new self($cells, $height);
    }

    /**
     * @param array<array-key, null|bool|DateInterval|DateTimeInterface|float|int|string|TextRun[]> $cellValues
     */
    public static function fromValuesWithStyle(array $cellValues, Style $cellStyle, float $height = self::DEFAULT_HEIGHT): self
    {
        $cells = array_map(static function (array|bool|DateInterval|DateTimeInterface|float|int|string|null $cellValue) use ($cellStyle): Cell {
            return Cell::fromValue($cellValue, $cellStyle);
        }, $cellValues);

        return new self(array_values($cells), $height);
    }
}
