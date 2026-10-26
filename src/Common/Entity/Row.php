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
    public const int DEFAULT_OUTLINE_LEVEL = 0;
    public const int MAX_OUTLINE_LEVEL = 7;

    /**
     * @param array<non-negative-int, Cell> $cells
     * @param int<0, 7>                     $outlineLevel Outline (group) level of the row
     * @param bool                          $hidden       Whether the row itself is hidden (as rows of a collapsed outline group have to be)
     * @param bool                          $collapsed    Whether the adjacent outline group one level deeper is collapsed
     */
    public function __construct(
        public array $cells,
        public float $height = self::DEFAULT_HEIGHT,
        public int $outlineLevel = self::DEFAULT_OUTLINE_LEVEL,
        public bool $hidden = false,
        public bool $collapsed = false,
    ) {
        if (0 > $this->outlineLevel || self::MAX_OUTLINE_LEVEL < $this->outlineLevel) {
            throw new InvalidArgumentException(\sprintf(
                'Outline level must be between 0 and %s, %s provided',
                self::MAX_OUTLINE_LEVEL,
                $this->outlineLevel
            ));
        }

        $lastIndex = -1;
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
            if ($index <= $lastIndex) {
                // see https://github.com/openspout/openspout/issues/362 for details
                throw new InvalidArgumentException(\sprintf(
                    'Cell indexes must be in ascending order, index %s is not greater than previous index %s. Run `\ksort()` on the cell array.',
                    $index,
                    $lastIndex
                ));
            }
            $lastIndex = $index;
        }
    }

    /**
     * @param array<non-negative-int, Cell> $cells
     */
    public function withCells(array $cells): self
    {
        return new self($cells, $this->height, $this->outlineLevel, $this->hidden, $this->collapsed);
    }

    public function withHeight(float $height): self
    {
        return new self($this->cells, $height, $this->outlineLevel, $this->hidden, $this->collapsed);
    }

    /**
     * @param int<0, 7> $outlineLevel
     */
    public function withOutlineLevel(int $outlineLevel): self
    {
        return new self($this->cells, $this->height, $outlineLevel, $this->hidden, $this->collapsed);
    }

    public function withHidden(bool $hidden): self
    {
        return new self($this->cells, $this->height, $this->outlineLevel, $hidden, $this->collapsed);
    }

    public function withCollapsed(bool $collapsed): self
    {
        return new self($this->cells, $this->height, $this->outlineLevel, $this->hidden, $collapsed);
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
     * @param int<0, 7>                                                                             $outlineLevel
     */
    public static function fromValues(array $cellValues, float $height = self::DEFAULT_HEIGHT, int $outlineLevel = self::DEFAULT_OUTLINE_LEVEL, bool $hidden = false, bool $collapsed = false): self
    {
        $cells = array_map(static function (array|bool|DateInterval|DateTimeInterface|float|int|string|null $cellValue): Cell {
            return Cell::fromValue($cellValue);
        }, $cellValues);

        return new self(array_values($cells), $height, $outlineLevel, $hidden, $collapsed);
    }

    /**
     * @param array<array-key, null|bool|DateInterval|DateTimeInterface|float|int|string|TextRun[]> $cellValues
     * @param array<array-key, Style>                                                               $columnStyles
     * @param int<0, 7>                                                                             $outlineLevel
     */
    public static function fromValuesWithStyles(array $cellValues, array $columnStyles, float $height = self::DEFAULT_HEIGHT, int $outlineLevel = self::DEFAULT_OUTLINE_LEVEL, bool $hidden = false, bool $collapsed = false): self
    {
        $cells = array_map(static function (array|bool|DateInterval|DateTimeInterface|float|int|string|null $cellValue, int|string $key) use ($columnStyles): Cell {
            return Cell::fromValue($cellValue, $columnStyles[$key] ?? null);
        }, $cellValues, array_keys($cellValues));

        return new self($cells, $height, $outlineLevel, $hidden, $collapsed);
    }

    /**
     * @param array<array-key, null|bool|DateInterval|DateTimeInterface|float|int|string|TextRun[]> $cellValues
     * @param int<0, 7>                                                                             $outlineLevel
     */
    public static function fromValuesWithStyle(array $cellValues, Style $cellStyle, float $height = self::DEFAULT_HEIGHT, int $outlineLevel = self::DEFAULT_OUTLINE_LEVEL, bool $hidden = false, bool $collapsed = false): self
    {
        $cells = array_map(static function (array|bool|DateInterval|DateTimeInterface|float|int|string|null $cellValue) use ($cellStyle): Cell {
            return Cell::fromValue($cellValue, $cellStyle);
        }, $cellValues);

        return new self(array_values($cells), $height, $outlineLevel, $hidden, $collapsed);
    }
}
