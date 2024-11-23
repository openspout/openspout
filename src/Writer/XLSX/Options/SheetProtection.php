<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Options;

final class SheetProtection
{
    public ?string $passwordHash = null;
    public bool $lockSheet = false;
    public bool $lockColumnInsert = false;
    public bool $lockColumnDelete = false;
    public bool $lockColumnFormatting = false;
    public bool $lockRowInsert = false;
    public bool $lockRowDelete = false;
    public bool $lockRowFormatting = false;
    public bool $lockAutoFilter = false;
    public bool $lockSort = false;
    public bool $lockCellFormatting = false;
    public bool $lockLockedCellSelection = false;
    public bool $lockUnlockedCellsSelection = false;
    public bool $lockObjects = false;
    public bool $lockHyperlinkInsert = false;
    public bool $lockPivotTables = false;
    public bool $lockScenarios = false;

    /**
     * @return $this
     */
    public function setPassword(string $password): self
    {
        $this->passwordHash = $this->createPasswordHash($password);

        return $this;
    }

    /**
     * @return $this
     */
    public function setLockSheet(bool $lockSheet): self
    {
        $this->lockSheet = $lockSheet;

        return $this;
    }

    /**
     * @return $this
     */
    public function setLockColumns(bool $lockColumn): self
    {
        $this->setLockColumnInsert($lockColumn);
        $this->setLockColumnDelete($lockColumn);
        $this->setLockColumnFormatting($lockColumn);

        return $this;
    }

    /**
     * @return $this
     */
    public function setLockColumnInsert(bool $lockColumnInsert): self
    {
        $this->lockColumnInsert = $lockColumnInsert;

        return $this;
    }

    /**
     * @return $this
     */
    public function setLockColumnDelete(bool $lockColumnDelete): self
    {
        $this->lockColumnDelete = $lockColumnDelete;

        return $this;
    }

    /**
     * @return $this
     */
    public function setLockColumnFormatting(bool $lockColumnFormatting): self
    {
        $this->lockColumnFormatting = $lockColumnFormatting;

        return $this;
    }

    /**
     * @return $this
     */
    public function setLockRows(bool $lockRow): self
    {
        $this->setLockRowInsert($lockRow);
        $this->setLockRowDelete($lockRow);
        $this->setLockRowFormatting($lockRow);

        return $this;
    }

    /**
     * @return $this
     */
    public function setLockRowInsert(bool $lockRowInsert): self
    {
        $this->lockRowInsert = $lockRowInsert;

        return $this;
    }

    /**
     * @return $this
     */
    public function setLockRowDelete(bool $lockRowDelete): self
    {
        $this->lockRowDelete = $lockRowDelete;

        return $this;
    }

    /**
     * @return $this
     */
    public function setLockRowFormatting(bool $lockRowFormatting): self
    {
        $this->lockRowFormatting = $lockRowFormatting;

        return $this;
    }

    /**
     * @return $this
     */
    public function setLockAutoFilter(bool $lockAutoFilter): self
    {
        $this->lockAutoFilter = $lockAutoFilter;

        return $this;
    }

    /**
     * @return $this
     */
    public function setLockSort(bool $lockSort): self
    {
        $this->lockSort = $lockSort;

        return $this;
    }

    /**
     * @return $this
     */
    public function setLockCellFormatting(bool $lockCellFormatting): self
    {
        $this->lockCellFormatting = $lockCellFormatting;

        return $this;
    }

    /**
     * @return $this
     */
    public function setLockLockedCellSelection(bool $lockLockedCellSelection): self
    {
        $this->lockLockedCellSelection = $lockLockedCellSelection;

        return $this;
    }

    /**
     * @return $this
     */
    public function setLockUnlockedCellsSelection(bool $lockUnlockedCellsSelection): self
    {
        $this->lockUnlockedCellsSelection = $lockUnlockedCellsSelection;

        return $this;
    }

    /**
     * @return $this
     */
    public function setLockObjects(bool $lockObjects): self
    {
        $this->lockObjects = $lockObjects;

        return $this;
    }

    /**
     * @return $this
     */
    public function setLockHyperlinkInsert(bool $lockHyperlinkInsert): self
    {
        $this->lockHyperlinkInsert = $lockHyperlinkInsert;

        return $this;
    }

    /**
     * @return $this
     */
    public function setLockPivotTables(bool $lockPivotTables): self
    {
        $this->lockPivotTables = $lockPivotTables;

        return $this;
    }

    /**
     * @return $this
     */
    public function setLockScenarios(bool $lockScenarios): self
    {
        $this->lockScenarios = $lockScenarios;

        return $this;
    }

    private function createPasswordHash(string $password): string
    {
        $verifier = 0;
        $pwlen = strlen($password);
        $passwordArray = pack('c', $pwlen) . $password;

        for ($i = $pwlen; $i >= 0; --$i) {
            $intermediate1 = (($verifier & 0x4000) === 0) ? 0 : 1;
            $intermediate2 = 2 * $verifier;
            $intermediate2 &= 0x7fff;
            $intermediate3 = $intermediate1 | $intermediate2;
            $verifier = $intermediate3 ^ ord($passwordArray[$i]);
        }

        $verifier ^= 0xCE4B;

        return strtoupper(dechex($verifier));
    }

    public function getXml(): string
    {
        return '<sheetProtection'.$this->getSheetViewAttributes().'></sheetProtection>';
    }

    private function getSheetViewAttributes(): string
    {
        return $this->generateAttributes([
            'password' => $this->passwordHash ?? '',
            'sheet' => $this->lockSheet,
            'objects' => $this->lockObjects,
            'scenarios' => $this->lockScenarios,
            'formatCells' => $this->lockCellFormatting,
            'formatColumns' => $this->lockColumnFormatting,
            'formatRows' => $this->lockRowFormatting,
            'insertColumns' => $this->lockColumnInsert,
            'insertRows' => $this->lockRowInsert,
            'deleteColumns' => $this->lockColumnDelete,
            'deleteRows' => $this->lockRowDelete,
            'selectLockedCells' => $this->lockLockedCellSelection,
            'selectUnlockedCells' => $this->lockUnlockedCellsSelection,
            'autoFilter' => $this->lockAutoFilter,
            'sort' => $this->lockSort,
            'hyperlink' => $this->lockHyperlinkInsert,
            'pivotTables' => $this->lockPivotTables,
        ]);
    }

    /**
     * @param array<string, bool|int|string> $data with key containing the attribute name and value containing the attribute value
     */
    private function generateAttributes(array $data): string
    {
        // Create attribute for each key
        $attributes = array_map(static function (string $key, bool|string $value): string {
            if (\is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            return $key.'="'.$value.'"';
        }, array_keys($data), $data);

        // Append all attributes
        return ' '.implode(' ', $attributes);
    }
}
