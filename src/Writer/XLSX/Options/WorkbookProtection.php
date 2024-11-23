<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Options;

final class WorkbookProtection
{
    private ?string $passwordHash = null;
    private bool $lockStructure = false;
    private bool $lockWindows = false;
    private bool $lockRevisions = false;

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
    public function setLockStructure(bool $lockStructure): self
    {
        $this->lockStructure = $lockStructure;

        return $this;
    }

    /**
     * @return $this
     */
    public function setLockWindows(bool $lockWindows): self
    {
        $this->lockWindows = $lockWindows;

        return $this;
    }

    /**
     * @return $this
     */
    public function setLockRevisions(bool $lockRevisions): self
    {
        $this->lockRevisions = $lockRevisions;

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
        return '<workbookProtection'.$this->getSheetViewAttributes().'/>';
    }

    private function getSheetViewAttributes(): string
    {
        return $this->generateAttributes([
            'workbookPassword' => $this->passwordHash ?? '',
            'lockStructure' => $this->lockStructure,
            'lockWindows' => $this->lockWindows,
            'lockRevisions' => $this->lockRevisions,
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
