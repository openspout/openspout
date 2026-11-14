<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX\Encryption;

/**
 * Builds Compound File Binary containers, to test their reader with content that is under control.
 *
 * Sectors are laid out in this order: DIFAT, FAT, directory, mini FAT, mini stream, streams.
 *
 * @internal
 */
final class CompoundFileBuilder
{
    public const END_OF_CHAIN = 0xFFFFFFFE;
    public const TYPE_STORAGE = 1;
    public const TYPE_STREAM = 2;

    private const FREE_SECTOR = 0xFFFFFFFF;
    private const FAT_SECTOR = 0xFFFFFFFD;
    private const DIFAT_SECTOR = 0xFFFFFFFC;
    private const MINI_STREAM_CUTOFF = 4096;

    /** @var int Offset, in the built file, of the first FAT sector */
    public int $fatOffset = 0;

    /** @var int Offset, in the built file, of the first directory sector */
    public int $directoryOffset = 0;

    /** @var int Offset, in the built file, of the first mini FAT sector */
    public int $miniFatOffset = 0;

    /** @var int Offset, in the built file, of the first DIFAT sector */
    public int $difatOffset = 0;

    /** @var list<array{name: string, type: int, data: string}> */
    private array $entries = [];

    private readonly int $sectorSize;

    public function __construct(private readonly int $sectorShift = 9)
    {
        $this->sectorSize = 1 << $sectorShift;
    }

    public function addEntry(string $name, string $data, int $type = self::TYPE_STREAM): self
    {
        $this->entries[] = ['name' => $name, 'type' => $type, 'data' => $data];

        return $this;
    }

    public function build(): string
    {
        $entriesPerSector = intdiv($this->sectorSize, 4);

        // Split the content of the streams between the mini stream and the regular sectors
        $miniStream = '';
        $miniFat = [];
        $regularData = '';
        $regularChains = [];
        $starts = [];
        foreach ($this->entries as $index => $entry) {
            $length = \strlen($entry['data']);
            if (0 === $length) {
                $starts[$index] = self::END_OF_CHAIN;
            } elseif ($length < self::MINI_STREAM_CUTOFF) {
                $numSectors = intdiv($length + 63, 64);
                $starts[$index] = \count($miniFat);
                for ($i = 1; $i <= $numSectors; ++$i) {
                    $miniFat[] = $i === $numSectors ? self::END_OF_CHAIN : \count($miniFat) + 1;
                }
                $miniStream .= str_pad($entry['data'], 64 * $numSectors, "\xEE");
            } else {
                $numSectors = intdiv($length + $this->sectorSize - 1, $this->sectorSize);
                $regularChains[$index] = [intdiv(\strlen($regularData), $this->sectorSize), $numSectors];
                $regularData .= str_pad($entry['data'], $this->sectorSize * $numSectors, "\xEE");
            }
        }

        $miniStreamLength = \strlen($miniStream);
        $miniStream = $this->padToSector($miniStream, "\xEE");
        $miniFatData = $this->padToSector(pack('V*', ...$miniFat), pack('V', self::FREE_SECTOR));
        $numDirectorySectors = intdiv((1 + \count($this->entries)) * 128 + $this->sectorSize - 1, $this->sectorSize);
        $numMiniFatSectors = intdiv(\strlen($miniFatData), $this->sectorSize);
        $numMiniStreamSectors = intdiv(\strlen($miniStream), $this->sectorSize);
        $numContentSectors = $numDirectorySectors + $numMiniFatSectors + $numMiniStreamSectors + intdiv(\strlen($regularData), $this->sectorSize);

        // The FAT describes every sector, including the ones of the FAT and of the DIFAT
        $numFatSectors = 0;
        $numDifatSectors = 0;
        do {
            $previous = [$numFatSectors, $numDifatSectors];
            $numFatSectors = intdiv($numContentSectors + $numFatSectors + $numDifatSectors + $entriesPerSector - 1, $entriesPerSector);
            $numDifatSectors = intdiv(max(0, $numFatSectors - 109) + $entriesPerSector - 2, $entriesPerSector - 1);
        } while ($previous !== [$numFatSectors, $numDifatSectors]);

        $directoryStart = $numDifatSectors + $numFatSectors;
        $miniFatStart = $directoryStart + $numDirectorySectors;
        $miniStreamStart = $miniFatStart + $numMiniFatSectors;
        $regularStart = $miniStreamStart + $numMiniStreamSectors;

        $fat = array_merge(
            array_fill(0, $numDifatSectors, self::DIFAT_SECTOR),
            array_fill(0, $numFatSectors, self::FAT_SECTOR),
            $this->chain($directoryStart, $numDirectorySectors),
            $this->chain($miniFatStart, $numMiniFatSectors),
            $this->chain($miniStreamStart, $numMiniStreamSectors),
        );
        foreach ($regularChains as $index => [$firstSector, $numSectors]) {
            $starts[$index] = $regularStart + $firstSector;
            $fat = array_merge($fat, $this->chain($regularStart + $firstSector, $numSectors));
        }

        // DIFAT: 109 entries in the header, the other ones in dedicated sectors that are chained
        $fatSectors = range($numDifatSectors, $numDifatSectors + $numFatSectors - 1);
        $headerDifat = array_pad(\array_slice($fatSectors, 0, 109), 109, self::FREE_SECTOR);
        $difat = '';
        foreach (array_chunk(\array_slice($fatSectors, 109), max(1, $entriesPerSector - 1)) as $index => $chunk) {
            $next = $index === $numDifatSectors - 1 ? self::END_OF_CHAIN : $index + 1;
            $difat .= pack('V*', ...array_pad($chunk, $entriesPerSector - 1, self::FREE_SECTOR), ...[$next]);
        }

        $directory = $this->directoryEntry('Root Entry', 5, 0 === $numMiniStreamSectors ? self::END_OF_CHAIN : $miniStreamStart, $miniStreamLength);
        foreach ($this->entries as $index => $entry) {
            $directory .= $this->directoryEntry($entry['name'], $entry['type'], $starts[$index], \strlen($entry['data']));
        }

        $header = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1".str_repeat("\0", 16)
            .pack('v4', 0x3E, 9 === $this->sectorShift ? 3 : 4, 0xFFFE, $this->sectorShift)
            .pack('v', 6).str_repeat("\0", 6)
            .pack(
                'V9',
                9 === $this->sectorShift ? 0 : $numDirectorySectors,
                $numFatSectors,
                $directoryStart,
                0,
                self::MINI_STREAM_CUTOFF,
                0 === $numMiniFatSectors ? self::END_OF_CHAIN : $miniFatStart,
                $numMiniFatSectors,
                0 === $numDifatSectors ? self::END_OF_CHAIN : 0,
                $numDifatSectors
            )
            .pack('V*', ...$headerDifat);

        $this->difatOffset = $this->sectorSize;
        $this->fatOffset = $this->sectorSize * (1 + $numDifatSectors);
        $this->directoryOffset = $this->sectorSize * (1 + $directoryStart);
        $this->miniFatOffset = $this->sectorSize * (1 + $miniFatStart);

        return $this->padToSector($header, "\0")
            .$difat
            .$this->padToSector(pack('V*', ...$fat), pack('V', self::FREE_SECTOR))
            .$this->padToSector($directory, "\0")
            .$miniFatData
            .$miniStream
            .$regularData;
    }

    /**
     * @return list<int>
     */
    private function chain(int $firstSector, int $numSectors): array
    {
        $chain = [];
        for ($i = 1; $i <= $numSectors; ++$i) {
            $chain[] = $i === $numSectors ? self::END_OF_CHAIN : $firstSector + $i;
        }

        return $chain;
    }

    private function directoryEntry(string $name, int $type, int $start, int $size): string
    {
        $encodedName = implode("\0", str_split($name))."\0\0\0";

        return str_pad($encodedName, 64, "\0")
            .pack('vCC', \strlen($encodedName), $type, 1)
            .pack('V3', self::FREE_SECTOR, self::FREE_SECTOR, self::FREE_SECTOR)
            .str_repeat("\0", 36)
            // The 32 upper bits of the size are not reliable when the sectors are 512 bytes long
            .pack('VVV', $start, $size, 9 === $this->sectorShift ? 0xDEADBEEF : 0);
    }

    private function padToSector(string $data, string $padding): string
    {
        $length = $this->sectorSize * intdiv(\strlen($data) + $this->sectorSize - 1, $this->sectorSize);

        return str_pad($data, $length, $padding);
    }
}
