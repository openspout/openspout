<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Encryption;

use OpenSpout\Common\Exception\IOException;

/**
 * Writes the Compound File Binary container (a.k.a. OLE2 file) of a password-protected Office document,
 * with the same content as the one written by Excel.
 *
 * The container is written sequentially, so that it can be sent to streams that cannot be rewound,
 * and what cannot be known before the package is encrypted is located after it. Sectors are laid out in
 * this order: DIFAT, FAT, encrypted package, directory, mini FAT, mini stream.
 *
 * @see https://learn.microsoft.com/openspecs/windows_protocols/ms-cfb
 * @see https://learn.microsoft.com/openspecs/office_file_formats/ms-offcrypto (2.1 and 2.2)
 *
 * @internal
 */
final class CompoundFileWriter
{
    /**
     * Files using 512 bytes sectors, which are the ones Excel writes, cannot be bigger than 2 GiB,
     * and their last sector would be reserved: this is how many sectors fit before it, header included.
     */
    private const MAX_SECTORS = 0x3FFFFF;

    private const SECTOR_SIZE = 512;
    private const MINI_SECTOR_SIZE = 64;

    /**
     * Streams smaller than this are stored in the mini stream.
     */
    private const MINI_STREAM_CUTOFF = 4096;
    private const FAT_ENTRIES_PER_SECTOR = 128;
    private const HEADER_DIFAT_ENTRIES = 109;
    private const DIRECTORY_SECTORS = 3;

    private const FREE_SECTOR = 0xFFFFFFFF;
    private const END_OF_CHAIN = 0xFFFFFFFE;
    private const FAT_SECTOR = 0xFFFFFFFD;
    private const DIFAT_SECTOR = 0xFFFFFFFC;
    private const NO_ENTRY = 0xFFFFFFFF;

    private const TYPE_UNUSED = 0;
    private const TYPE_STORAGE = 1;
    private const TYPE_STREAM = 2;
    private const TYPE_ROOT = 5;
    private const RED = 0;
    private const BLACK = 1;

    // Content of the streams telling that the "EncryptedPackage" stream is encrypted
    private const VERSION = '3c0000004d006900630072006f0073006f00660074002e0043006f006e007400610069006e00650072002e00440061007400610053007000610063006500730001000000'
        .'0100000001000000';
    private const DATA_SPACE_MAP = '08000000010000006800000001000000000000002000000045006e0063007200790070007400650064005000610063006b00610067006500320000005300740072006f00'
        .'6e00670045006e006300720079007000740069006f006e004400610074006100530070006100630065000000';
    private const STRONG_ENCRYPTION_DATA_SPACE = '0800000001000000320000005300740072006f006e00670045006e006300720079007000740069006f006e005400720061006e00730066006f0072006d000000';
    private const PRIMARY = '58000000010000004c0000007b00460046003900410033004600300033002d0035003600450046002d0034003600310033002d0042004400440035002d00350041003400'
        .'3100430031004400300037003200340036007d004e0000004d006900630072006f0073006f00660074002e0043006f006e007400610069006e00650072002e0045006e00'
        .'6300720079007000740069006f006e005400720061006e00730066006f0072006d00000001000000010000000100000000000000000000000000000004000000';

    private readonly bool $isPackageInMiniStream;
    private readonly int $numDifatSectors;
    private readonly int $numFatSectors;
    private readonly int $numPackageSectors;
    private readonly int $numMiniFatSectors;
    private readonly int $numMiniStreamSectors;

    /** @var string Content of the package, when it is small enough to be stored in the mini stream */
    private string $miniPackage = '';

    private bool $isStarted = false;

    /**
     * @param resource $stream      Where the container gets written
     * @param int      $packageSize Size of the encrypted package
     *
     * @throws IOException If the container would be too big
     */
    public function __construct(
        private $stream,
        private readonly int $packageSize,
        private readonly int $encryptionInfoSize,
    ) {
        $this->isPackageInMiniStream = $packageSize < self::MINI_STREAM_CUTOFF;
        $this->numPackageSectors = $this->isPackageInMiniStream ? 0 : self::count($packageSize, self::SECTOR_SIZE);

        $numMiniSectors = 0;
        foreach ($this->miniStreamSizes() as $size) {
            $numMiniSectors += self::count($size, self::MINI_SECTOR_SIZE);
        }
        $this->numMiniFatSectors = self::count($numMiniSectors, self::FAT_ENTRIES_PER_SECTOR);
        $this->numMiniStreamSectors = self::count($numMiniSectors * self::MINI_SECTOR_SIZE, self::SECTOR_SIZE);

        // The FAT describes every sector, including the ones of the FAT and of the DIFAT
        $numContentSectors = $this->numPackageSectors + self::DIRECTORY_SECTORS + $this->numMiniFatSectors + $this->numMiniStreamSectors;
        $numFatSectors = 0;
        $numDifatSectors = 0;
        do {
            $previous = $numFatSectors + $numDifatSectors;
            $numFatSectors = self::count($numContentSectors + $previous, self::FAT_ENTRIES_PER_SECTOR);
            // The first FAT sectors are listed in the header, the other ones in DIFAT sectors, which also point to the next one
            $numDifatSectors = self::count(max(0, $numFatSectors - self::HEADER_DIFAT_ENTRIES), self::FAT_ENTRIES_PER_SECTOR - 1);
        } while ($previous !== $numFatSectors + $numDifatSectors);
        $this->numFatSectors = $numFatSectors;
        $this->numDifatSectors = $numDifatSectors;

        // The header takes the place of a sector
        if (1 + $numDifatSectors + $numFatSectors + $numContentSectors > self::MAX_SECTORS) {
            throw new IOException('The spreadsheet is too big to be password-protected.');
        }
    }

    public function writePackage(string $data): void
    {
        if ($this->isPackageInMiniStream) {
            $this->miniPackage .= $data;

            return;
        }

        $this->start();
        fwrite($this->stream, $data);
    }

    /**
     * Writes what follows the encrypted package: it has to be called once the whole package has been written.
     *
     * @param string $encryptionInfo Content of the "EncryptionInfo" stream
     */
    public function finish(string $encryptionInfo): void
    {
        \assert(\strlen($encryptionInfo) === $this->encryptionInfoSize);
        \assert(\strlen($this->miniPackage) === ($this->isPackageInMiniStream ? $this->packageSize : 0));

        $this->start();
        fwrite($this->stream, str_repeat("\0", self::SECTOR_SIZE * $this->numPackageSectors - ($this->isPackageInMiniStream ? 0 : $this->packageSize)));

        $miniStreams = array_combine(array_keys($this->miniStreamSizes()), [
            self::binary(self::VERSION),
            self::binary(self::DATA_SPACE_MAP),
            self::binary(self::STRONG_ENCRYPTION_DATA_SPACE),
            self::binary(self::PRIMARY),
            $encryptionInfo,
            $this->miniPackage,
        ]);

        // Streams of the mini stream are stored one after the other
        $miniFat = [];
        $miniStream = '';
        $miniStarts = [];
        foreach ($miniStreams as $name => $content) {
            $miniStarts[$name] = \count($miniFat);
            $numMiniSectors = self::count(\strlen($content), self::MINI_SECTOR_SIZE);
            for ($i = 1; $i <= $numMiniSectors; ++$i) {
                $miniFat[] = $i === $numMiniSectors ? self::END_OF_CHAIN : \count($miniFat) + 1;
            }
            $miniStream .= str_pad($content, self::MINI_SECTOR_SIZE * $numMiniSectors, "\0");
        }

        $packageStart = $this->numDifatSectors + $this->numFatSectors;
        $miniStreamStart = $packageStart + $this->numPackageSectors + self::DIRECTORY_SECTORS + $this->numMiniFatSectors;
        $stream = static function (string $name, int $color, int $left = self::NO_ENTRY, int $right = self::NO_ENTRY) use ($miniStarts, $miniStreams): string {
            return self::directoryEntry($name, self::TYPE_STREAM, $color, $left, $right, self::NO_ENTRY, $miniStarts[$name], \strlen($miniStreams[$name]));
        };
        $storage = static function (string $name, int $color, int $child, int $right = self::NO_ENTRY): string {
            return self::directoryEntry($name, self::TYPE_STORAGE, $color, self::NO_ENTRY, $right, $child, 0, 0);
        };

        // Siblings are organized as a red-black tree, sorted by length of the name then by name: this is what Excel writes
        $directory = self::directoryEntry('Root Entry', self::TYPE_ROOT, self::RED, self::NO_ENTRY, self::NO_ENTRY, 10, $miniStreamStart, \strlen($miniStream))
            .($this->isPackageInMiniStream
                ? $stream('EncryptedPackage', self::RED)
                : self::directoryEntry('EncryptedPackage', self::TYPE_STREAM, self::RED, self::NO_ENTRY, self::NO_ENTRY, self::NO_ENTRY, $packageStart, $this->packageSize))
            .$storage("\x06DataSpaces", self::RED, 4)
            .$stream('Version', self::BLACK)
            .$stream('DataSpaceMap', self::BLACK, 3, 5)
            .$storage('DataSpaceInfo', self::BLACK, 6, 7)
            .$stream('StrongEncryptionDataSpace', self::BLACK)
            .$storage('TransformInfo', self::RED, 8)
            .$storage('StrongEncryptionTransform', self::BLACK, 9)
            .$stream("\x06Primary", self::BLACK)
            .$stream('EncryptionInfo', self::BLACK, 2, 1)
            .self::directoryEntry('', self::TYPE_UNUSED, self::RED, self::NO_ENTRY, self::NO_ENTRY, self::NO_ENTRY, 0, 0);

        fwrite($this->stream, $directory);
        fwrite($this->stream, str_pad(pack('V*', ...$miniFat), self::SECTOR_SIZE * $this->numMiniFatSectors, pack('V', self::FREE_SECTOR)));
        fwrite($this->stream, str_pad($miniStream, self::SECTOR_SIZE * $this->numMiniStreamSectors, "\0"));
    }

    /**
     * Writes what precedes the encrypted package, unless it is already done: the header, the DIFAT and the FAT.
     */
    private function start(): void
    {
        if ($this->isStarted) {
            return;
        }
        $this->isStarted = true;

        $fatStart = $this->numDifatSectors;
        $packageStart = $fatStart + $this->numFatSectors;
        $directoryStart = $packageStart + $this->numPackageSectors;
        $miniFatStart = $directoryStart + self::DIRECTORY_SECTORS;
        $miniStreamStart = $miniFatStart + $this->numMiniFatSectors;
        $numSectors = $miniStreamStart + $this->numMiniStreamSectors;

        // Header: signature, class identifier, minor and major versions, byte order, sizes of the sectors, as powers of 2...
        $header = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1".str_repeat("\0", 16)
            .pack('v5', 0x3E, 3, 0xFFFE, 9, 6).str_repeat("\0", 6)
            // ... and where everything is
            .pack(
                'V9',
                0,
                $this->numFatSectors,
                $directoryStart,
                0,
                self::MINI_STREAM_CUTOFF,
                $miniFatStart,
                $this->numMiniFatSectors,
                0 === $this->numDifatSectors ? self::END_OF_CHAIN : 0,
                $this->numDifatSectors
            );
        for ($i = 0; $i < self::HEADER_DIFAT_ENTRIES; ++$i) {
            $header .= pack('V', $i < $this->numFatSectors ? $fatStart + $i : self::FREE_SECTOR);
        }
        fwrite($this->stream, $header);

        // DIFAT: location of the FAT sectors that are not listed in the header
        for ($difatSector = 0; $difatSector < $this->numDifatSectors; ++$difatSector) {
            $entries = '';
            $firstFatSector = self::HEADER_DIFAT_ENTRIES + $difatSector * (self::FAT_ENTRIES_PER_SECTOR - 1);
            for ($i = $firstFatSector; $i < $firstFatSector + self::FAT_ENTRIES_PER_SECTOR - 1; ++$i) {
                $entries .= pack('V', $i < $this->numFatSectors ? $fatStart + $i : self::FREE_SECTOR);
            }
            fwrite($this->stream, $entries.pack('V', $difatSector === $this->numDifatSectors - 1 ? self::END_OF_CHAIN : $difatSector + 1));
        }

        // FAT: every stream is made of consecutive sectors, so each sector is followed by the next one, except the last one of a stream
        $lastSectors = [$directoryStart - 1, $miniFatStart - 1, $miniStreamStart - 1, $numSectors - 1];
        for ($fatSector = 0; $fatSector < $this->numFatSectors; ++$fatSector) {
            $entries = [];
            for ($sector = $fatSector * self::FAT_ENTRIES_PER_SECTOR; \count($entries) < self::FAT_ENTRIES_PER_SECTOR; ++$sector) {
                $entries[] = match (true) {
                    $sector < $fatStart => self::DIFAT_SECTOR,
                    $sector < $packageStart => self::FAT_SECTOR,
                    $sector >= $numSectors => self::FREE_SECTOR,
                    \in_array($sector, $lastSectors, true) => self::END_OF_CHAIN,
                    default => $sector + 1,
                };
            }
            fwrite($this->stream, pack('V*', ...$entries));
        }
    }

    /**
     * @return array<string, int> Size of the streams that are stored in the mini stream, by name
     */
    private function miniStreamSizes(): array
    {
        return [
            'Version' => intdiv(\strlen(self::VERSION), 2),
            'DataSpaceMap' => intdiv(\strlen(self::DATA_SPACE_MAP), 2),
            'StrongEncryptionDataSpace' => intdiv(\strlen(self::STRONG_ENCRYPTION_DATA_SPACE), 2),
            "\x06Primary" => intdiv(\strlen(self::PRIMARY), 2),
            'EncryptionInfo' => $this->encryptionInfoSize,
            'EncryptedPackage' => $this->isPackageInMiniStream ? $this->packageSize : 0,
        ];
    }

    private static function directoryEntry(string $name, int $type, int $color, int $left, int $right, int $child, int $start, int $size): string
    {
        // Names are stored as null terminated UTF-16LE strings
        $encodedName = '' === $name ? '' : implode("\0", str_split($name))."\0\0\0";

        return str_pad($encodedName, 64, "\0")
            .pack('vCCV3', \strlen($encodedName), $type, $color, $left, $right, $child)
            // Class identifier, state and timestamps are not used
            .str_repeat("\0", 36)
            .pack('VP', $start, $size);
    }

    private static function binary(string $hexadecimal): string
    {
        $binary = hex2bin($hexadecimal);
        \assert(false !== $binary);

        return $binary;
    }

    /**
     * @return int How many blocks of the given size are needed to store the given length
     */
    private static function count(int $length, int $blockSize): int
    {
        return intdiv($length + $blockSize - 1, $blockSize);
    }
}
