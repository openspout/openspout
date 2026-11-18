<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX\Encryption;

use Generator;
use OpenSpout\Common\Helper\Encryption\Binary;
use OpenSpout\Reader\Exception\EncryptedFileCorruptedException;

/**
 * Minimal, streaming reader for Compound File Binary containers (a.k.a. OLE2 files),
 * which is the container used by password-protected Office documents.
 *
 * Streams are looked up by name among all the directory entries: storages are not
 * distinguished, which is enough for the flat layout of encrypted Office documents.
 *
 * The file is kept open for as long as the instance exists.
 *
 * @see https://learn.microsoft.com/openspecs/windows_protocols/ms-cfb
 *
 * @internal
 */
final class CompoundFile
{
    private const SIGNATURE = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";
    private const HEADER_SIZE = 512;
    private const HEADER_DIFAT_ENTRIES = 109;
    private const END_OF_CHAIN = 0xFFFFFFFE;
    private const DIRECTORY_ENTRY_SIZE = 128;
    private const DIRECTORY_ENTRY_TYPE_STREAM = 2;
    private const MINI_SECTOR_SIZE = 64;

    /**
     * Streams smaller than this are stored in the mini stream.
     */
    private const MINI_STREAM_CUTOFF = 4096;

    /** @var resource */
    private $handle;

    private readonly int $sectorSize;
    private readonly int $directoryStart;
    private readonly int $miniFatStart;

    /** @var int How many sectors fit in the file, which is as many as what can be read from it */
    private readonly int $numSectors;

    /** @var string The File Allocation Table: one little-endian uint32 per sector */
    private string $fat = '';

    /**
     * @throws EncryptedFileCorruptedException If the file is not a valid compound file
     */
    public function __construct(string $filePath)
    {
        $handle = fopen($filePath, 'r');
        \assert(false !== $handle);
        $this->handle = $handle;

        $header = fread($this->handle, self::HEADER_SIZE);
        \assert(false !== $header);
        if (self::HEADER_SIZE !== \strlen($header) || !str_starts_with($header, self::SIGNATURE)) {
            throw new EncryptedFileCorruptedException('The file is not a compound file.');
        }

        $this->sectorSize = match (Binary::uint16($header, 0x1E)) {
            9 => 512,
            12 => 4096,
            default => throw new EncryptedFileCorruptedException('The compound file uses an invalid sector size.'),
        };
        $stat = fstat($this->handle);
        \assert(false !== $stat);
        $this->numSectors = intdiv($stat['size'], $this->sectorSize);
        $this->directoryStart = Binary::uint32($header, 0x30);
        $this->miniFatStart = Binary::uint32($header, 0x3C);

        $this->loadFat($header);
    }

    public static function isCompoundFile(string $filePath): bool
    {
        return self::SIGNATURE === file_get_contents($filePath, length: \strlen(self::SIGNATURE));
    }

    public function hasStream(string $name): bool
    {
        return null !== $this->findStream($name);
    }

    /**
     * Returns the whole content of a stream that is expected to be small.
     *
     * @throws EncryptedFileCorruptedException If the stream is missing, broken or bigger than $maxLength
     */
    public function readStream(string $name, int $maxLength): string
    {
        $data = '';
        foreach ($this->streamChunks($name) as $chunk) {
            $data .= $chunk;
            if (\strlen($data) > $maxLength) {
                throw new EncryptedFileCorruptedException("The \"{$name}\" stream is too big.");
            }
        }

        return $data;
    }

    /**
     * Returns the content of a stream piece by piece, so that it never has to fit in memory.
     *
     * @return Generator<int, string>
     *
     * @throws EncryptedFileCorruptedException If the stream is missing or broken
     */
    public function streamChunks(string $name): Generator
    {
        $stream = $this->findStream($name);
        if (null === $stream) {
            throw new EncryptedFileCorruptedException("The \"{$name}\" stream is missing.");
        }
        [$sector, $size] = $stream;

        if ($size < self::MINI_STREAM_CUTOFF) {
            // The root entry is the first entry of the directory, and holds the location of the mini stream
            $miniStreamStart = Binary::uint32($this->readFromChain($this->directoryStart, 0, self::DIRECTORY_ENTRY_SIZE), 0x74);
            for (; $size > 0; $size -= self::MINI_SECTOR_SIZE) {
                yield substr($this->readFromChain($miniStreamStart, $sector * self::MINI_SECTOR_SIZE, self::MINI_SECTOR_SIZE), 0, $size);
                $sector = Binary::uint32($this->readFromChain($this->miniFatStart, $sector * 4, 4), 0);
            }

            return;
        }

        foreach ($this->chain($sector) as $chainedSector) {
            yield substr($this->readSector($chainedSector), 0, $size);
            $size -= $this->sectorSize;
            if ($size <= 0) {
                return;
            }
        }

        throw new EncryptedFileCorruptedException("The \"{$name}\" stream is truncated.");
    }

    /**
     * The FAT is itself stored in sectors, which are listed in the DIFAT:
     * its first entries are in the header, the following ones in a chain of dedicated sectors.
     */
    private function loadFat(string $header): void
    {
        $numFatSectors = Binary::uint32($header, 0x2C);
        if ($numFatSectors > $this->numSectors) {
            throw new EncryptedFileCorruptedException('The compound file allocation table is bigger than the file.');
        }

        $fatSectors = self::uint32List(substr($header, 0x4C, 4 * self::HEADER_DIFAT_ENTRIES));
        $difatSector = Binary::uint32($header, 0x44);
        while (\count($fatSectors) < $numFatSectors) {
            $entries = self::uint32List($this->readSector($difatSector));
            // The last entry of a DIFAT sector points to the next one
            $difatSector = array_pop($entries);
            array_push($fatSectors, ...$entries);
        }

        foreach (\array_slice($fatSectors, 0, $numFatSectors) as $fatSector) {
            $this->fat .= $this->readSector($fatSector);
        }
    }

    /**
     * @return null|array{int, int} First sector and size of the stream, or null if there is no such stream
     */
    private function findStream(string $name): ?array
    {
        // Entry names are stored as null terminated UTF-16LE strings
        $encodedName = implode("\0", str_split($name))."\0";

        foreach ($this->chain($this->directoryStart) as $sector) {
            foreach (str_split($this->readSector($sector), self::DIRECTORY_ENTRY_SIZE) as $entry) {
                if (self::DIRECTORY_ENTRY_TYPE_STREAM === \ord($entry[0x42])
                    && \strlen($encodedName) + 2 === Binary::uint16($entry, 0x40)
                    && str_starts_with($entry, $encodedName)
                ) {
                    // The size is a 64 bits integer, but only its 32 lower bits are reliable in files using 512 bytes sectors
                    $size = 512 === $this->sectorSize ? Binary::uint32($entry, 0x78) : Binary::uint64($entry, 0x78);

                    return [Binary::uint32($entry, 0x74), $size];
                }
            }
        }

        return null;
    }

    /**
     * Reads a few bytes, located in a single sector, of the sector chain starting at the given sector.
     */
    private function readFromChain(int $startSector, int $offset, int $length): string
    {
        $targetIndex = intdiv($offset, $this->sectorSize);
        foreach ($this->chain($startSector) as $index => $sector) {
            if ($index === $targetIndex) {
                return substr($this->readSector($sector), $offset % $this->sectorSize, $length);
            }
        }

        throw new EncryptedFileCorruptedException('The compound file refers to data outside of a sector chain.');
    }

    /**
     * Follows the FAT, starting at the given sector, until the end of the chain.
     *
     * @return Generator<int, int>
     */
    private function chain(int $startSector): Generator
    {
        $numFatEntries = intdiv(\strlen($this->fat), 4);
        for ($sector = $startSector, $length = 0; self::END_OF_CHAIN !== $sector; ++$length) {
            // A chain cannot have more sectors than the file, unless it loops: what is read is never bigger than the file
            if ($sector >= $numFatEntries || $length === $this->numSectors) {
                throw new EncryptedFileCorruptedException('The compound file contains an invalid sector chain.');
            }

            yield $sector;

            $sector = Binary::uint32($this->fat, 4 * $sector);
        }
    }

    private function readSector(int $sector): string
    {
        // Sector 0 comes right after the header, which always occupies a whole sector
        fseek($this->handle, ($sector + 1) * $this->sectorSize);
        $data = fread($this->handle, $this->sectorSize);
        \assert(false !== $data);
        if (\strlen($data) !== $this->sectorSize) {
            throw new EncryptedFileCorruptedException('The compound file refers to a sector outside of the file.');
        }

        return $data;
    }

    /**
     * @return array<int, int>
     */
    private static function uint32List(string $data): array
    {
        /** @var array<int, int>|false $values */
        $values = unpack('V*', $data);
        \assert(false !== $values);

        return $values;
    }
}
