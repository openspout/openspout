<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Encryption;

use OpenSpout\Common\Exception\IOException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CompoundFileWriterTest extends TestCase
{
    private const FREE_SECTOR = 0xFFFFFFFF;
    private const END_OF_CHAIN = 0xFFFFFFFE;
    private const FAT_SECTOR = 0xFFFFFFFD;
    private const DIFAT_SECTOR = 0xFFFFFFFC;

    private const VERSION = '3c0000004d006900630072006f0073006f00660074002e0043006f006e007400610069006e00650072002e004400610074006100530070006100630065007300010000000100000001000000';
    private const DATA_SPACE_MAP = '08000000010000006800000001000000000000002000000045006e0063007200790070007400650064005000610063006b00610067006500320000005300740072006f006e00670045006e006300720079007000740069006f006e004400610074006100530070006100630065000000';
    private const STRONG_ENCRYPTION_DATA_SPACE = '0800000001000000320000005300740072006f006e00670045006e006300720079007000740069006f006e005400720061006e00730066006f0072006d000000';
    private const PRIMARY = '58000000010000004c0000007b00460046003900410033004600300033002d0035003600450046002d0034003600310033002d0042004400440035002d003500410034003100430031004400300037003200340036007d004e0000004d006900630072006f0073006f00660074002e0043006f006e007400610069006e00650072002e0045006e006300720079007000740069006f006e005400720061006e00730066006f0072006d00000001000000010000000100000000000000000000000000000004000000';

    /**
     * @param int $expectedNumFatSectors   109 FAT sectors is the most that can be listed in the header
     * @param int $expectedNumDifatSectors A DIFAT sector lists 127 FAT sectors
     */
    #[DataProvider('provideWriteCases')]
    public function testWrite(int $packageSize, int $encryptionInfoSize, int $expectedNumFatSectors, int $expectedNumDifatSectors): void
    {
        $package = self::data($packageSize, 'package');
        $encryptionInfo = self::data($encryptionInfoSize, 'info');

        $file = self::write($package, $encryptionInfo);
        $parsed = self::parse($file);

        self::assertSame($expectedNumFatSectors, $parsed['header']['numFatSectors']);
        self::assertSame($expectedNumDifatSectors, $parsed['header']['numDifatSectors']);
        self::assertSame(md5($package), md5($parsed['streams']['EncryptedPackage']));
        self::assertSame($encryptionInfo, $parsed['streams']['EncryptionInfo']);
        self::assertSame(self::VERSION, bin2hex($parsed['streams']['Version']));
        self::assertSame(self::DATA_SPACE_MAP, bin2hex($parsed['streams']['DataSpaceMap']));
        self::assertSame(self::STRONG_ENCRYPTION_DATA_SPACE, bin2hex($parsed['streams']['StrongEncryptionDataSpace']));
        self::assertSame(self::PRIMARY, bin2hex($parsed['streams']["\x06Primary"]));

        // Same tree as the one written by Excel
        $mini = $packageSize < 4096;
        self::assertSame([
            ['Root Entry', 5, 0, null, null, 10],
            ['EncryptedPackage', 2, 0, null, null, null],
            ["\x06DataSpaces", 1, 0, null, null, 4],
            ['Version', 2, 1, null, null, null],
            ['DataSpaceMap', 2, 1, 3, 5, null],
            ['DataSpaceInfo', 1, 1, null, 7, 6],
            ['StrongEncryptionDataSpace', 2, 1, null, null, null],
            ['TransformInfo', 1, 0, null, null, 8],
            ['StrongEncryptionTransform', 1, 1, null, null, 9],
            ["\x06Primary", 2, 1, null, null, null],
            ['EncryptionInfo', 2, 1, 2, 1, null],
            ['', 0, 0, null, null, null],
        ], $parsed['tree']);

        $miniSectors = static fn (int $size): int => intdiv($size + 63, 64);
        $infoStart = 9;
        $packageMiniStart = $infoStart + $miniSectors($encryptionInfoSize);
        $numMiniSectors = $packageMiniStart + ($mini ? $miniSectors($packageSize) : 0);
        $numMiniFatSectors = intdiv($numMiniSectors + 127, 128);
        self::assertSame($numMiniFatSectors, $parsed['header']['numMiniFatSectors']);
        $packageStart = $expectedNumDifatSectors + $expectedNumFatSectors;
        $directoryStart = $packageStart + ($mini ? 0 : intdiv($packageSize + 511, 512));
        self::assertSame([
            'Root Entry' => [$directoryStart + 3 + $numMiniFatSectors, 64 * $numMiniSectors],
            'EncryptedPackage' => [$mini ? $packageMiniStart : $packageStart, $packageSize],
            "\x06DataSpaces" => [0, 0],
            'Version' => [0, 76],
            'DataSpaceMap' => [2, 112],
            'DataSpaceInfo' => [0, 0],
            'StrongEncryptionDataSpace' => [4, 64],
            'TransformInfo' => [0, 0],
            'StrongEncryptionTransform' => [0, 0],
            "\x06Primary" => [5, 200],
            'EncryptionInfo' => [$infoStart, $encryptionInfoSize],
            '' => [0, 0],
        ], $parsed['locations']);
        self::assertSame($directoryStart, $parsed['header']['directoryStart']);
        self::assertSame($directoryStart + 3, $parsed['header']['miniFatStart']);
    }

    public static function provideWriteCases(): iterable
    {
        // The package is stored in the mini stream when it is smaller than 4096 bytes
        foreach ([8, 24, 63, 64, 65, 4095] as $packageSize) {
            yield "package of {$packageSize} bytes, in the mini stream" => [$packageSize, 1211, 1, 0];
        }
        foreach ([4096, 4097, 4607, 4608, 4609, 10000, 60000] as $packageSize) {
            yield "package of {$packageSize} bytes" => [$packageSize, 1211, 1, 0];
        }
        foreach ([1, 64, 65, 2000, 4095] as $encryptionInfoSize) {
            yield "encryption information of {$encryptionInfoSize} bytes" => [5000, $encryptionInfoSize, 1, 0];

            yield "encryption information of {$encryptionInfoSize} bytes, package in the mini stream" => [100, $encryptionInfoSize, 1, 0];
        }

        // A mini FAT sector describes 128 mini sectors, and 9 of them are used by the streams that never change
        yield 'mini stream filling 1 mini FAT sector' => [64 * 59, 64 * 60, 1, 0];

        yield 'mini stream needing 2 mini FAT sectors' => [64 * 59 + 1, 64 * 60, 1, 0];

        // 128 sectors are described by a FAT sector, and 8 sectors are used by the directory, the mini FAT and the mini stream
        yield 'package filling 1 FAT sector' => [512 * 119, 1211, 1, 0];

        yield 'package needing 2 FAT sectors' => [512 * 119 + 1, 1211, 2, 0];

        yield 'package filling 109 FAT sectors' => [512 * (109 * 128 - 109 - 8), 1211, 109, 0];

        yield 'package needing 1 DIFAT sector' => [512 * (109 * 128 - 109 - 8) + 1, 1211, 110, 1];

        yield 'package filling 1 DIFAT sector' => [512 * (236 * 128 - 236 - 1 - 8), 1211, 236, 1];

        yield 'package needing 2 DIFAT sectors' => [512 * (236 * 128 - 236 - 1 - 8) + 1, 1211, 237, 2];
    }

    public function testWriteShouldNotDependOnHowPackageIsGiven(): void
    {
        foreach ([100, 10000] as $packageSize) {
            $package = self::data($packageSize, 'package');
            $encryptionInfo = self::data(1211, 'info');

            $stream = fopen('php://memory', 'w+');
            self::assertIsResource($stream);
            $writer = new CompoundFileWriter($stream, $packageSize, 1211);
            foreach (str_split($package, 37) as $chunk) {
                $writer->writePackage($chunk);
            }
            $writer->finish($encryptionInfo);

            self::assertSame(self::write($package, $encryptionInfo), stream_get_contents($stream, null, 0));
        }
    }

    public function testNothingShouldBeWrittenBeforePackage(): void
    {
        $stream = fopen('php://memory', 'w+');
        self::assertIsResource($stream);

        new CompoundFileWriter($stream, 10000, 1211);

        self::assertSame('', stream_get_contents($stream, null, 0));
    }

    public function testBiggestPackage(): void
    {
        $stream = fopen('php://memory', 'w+');
        self::assertIsResource($stream);

        // Besides the package: the header, 3 directory sectors, 1 mini FAT sector, 4 mini stream sectors, the FAT and the DIFAT
        $numSectors = static function (int $numPackageSectors): int {
            $numFatSectors = 0;
            $numDifatSectors = 0;
            do {
                $previous = $numFatSectors + $numDifatSectors;
                $numFatSectors = (int) ceil(($numPackageSectors + 8 + $previous) / 128);
                $numDifatSectors = (int) ceil(max(0, $numFatSectors - 109) / 127);
            } while ($previous !== $numFatSectors + $numDifatSectors);

            return 1 + $numPackageSectors + 8 + $numFatSectors + $numDifatSectors;
        };
        // Files cannot reach the last 256 bytes of the 2 GiB that 512 bytes sectors allow
        $maxNumSectors = intdiv(2 ** 31 - 256, 512);
        $numPackageSectors = $maxNumSectors;
        while ($numSectors($numPackageSectors) > $maxNumSectors) {
            --$numPackageSectors;
        }
        self::assertSame($maxNumSectors, $numSectors($numPackageSectors));
        self::assertSame(4161268, $numPackageSectors);

        new CompoundFileWriter($stream, 512 * $numPackageSectors, 1211);

        try {
            new CompoundFileWriter($stream, 512 * $numPackageSectors + 1, 1211);
            self::fail('The package should have been rejected');
        } catch (IOException $exception) {
            self::assertSame('The spreadsheet is too big to be password-protected.', $exception->getMessage());
        }
        self::assertSame('', stream_get_contents($stream, null, 0), 'Nothing should be written for a package that is rejected');
    }

    private static function write(string $package, string $encryptionInfo): string
    {
        $stream = fopen('php://memory', 'w+');
        self::assertIsResource($stream);

        $writer = new CompoundFileWriter($stream, \strlen($package), \strlen($encryptionInfo));
        $writer->writePackage($package);
        $writer->finish($encryptionInfo);

        $file = stream_get_contents($stream, null, 0);
        self::assertIsString($file);

        return $file;
    }

    /**
     * Reads a compound file, checking that it is consistent, without relying on the code of the library.
     *
     * @return array{
     *     header: array<string, int>,
     *     tree: list<array{string, int, int, null|int, null|int, null|int}>,
     *     locations: array<string, array{int, int}>,
     *     streams: array<string, string>
     * }
     */
    private static function parse(string $file): array
    {
        self::assertSame(0, \strlen($file) % 512, 'The file should be made of whole sectors');
        $numSectors = intdiv(\strlen($file), 512) - 1;
        $sector = static fn (int $index): string => substr($file, 512 * ($index + 1), 512);
        $ints = static function (string $data): array {
            $values = unpack('V*', $data);
            self::assertIsArray($values);

            return array_values($values);
        };

        self::assertSame('d0cf11e0a1b11ae1'.str_repeat('00', 16).'3e000300feff09000600'.str_repeat('00', 6), bin2hex(substr($file, 0, 40)));

        /** @var array<string, int> $header */
        $header = unpack('VnumDirectorySectors/VnumFatSectors/VdirectoryStart/Vtransaction/VminiStreamCutoff/VminiFatStart/VnumMiniFatSectors/VdifatStart/VnumDifatSectors', $file, 0x28);
        self::assertSame(0, $header['numDirectorySectors']);
        self::assertSame(0, $header['transaction']);
        self::assertSame(4096, $header['miniStreamCutoff']);

        $usage = [];

        // DIFAT
        $difat = $ints(substr($file, 0x4C, 4 * 109));
        $numDifatSectors = 0;
        for ($index = $header['difatStart']; self::END_OF_CHAIN !== $index; ++$numDifatSectors) {
            self::assertLessThan($numSectors, $index);
            $usage[$index][] = 'DIFAT';
            $entries = $ints($sector($index));
            $index = array_pop($entries);
            array_push($difat, ...$entries);
        }
        self::assertSame($header['numDifatSectors'], $numDifatSectors);
        $fatSectors = \array_slice($difat, 0, $header['numFatSectors']);
        self::assertCount($header['numFatSectors'], $fatSectors);
        self::assertSame([], array_diff(\array_slice($difat, $header['numFatSectors']), [self::FREE_SECTOR]), 'The unused entries of the DIFAT should be free');

        // FAT
        $fat = [];
        foreach ($fatSectors as $index) {
            self::assertLessThan($numSectors, $index);
            $usage[$index][] = 'FAT';
            array_push($fat, ...$ints($sector($index)));
        }
        self::assertSame([], array_diff(\array_slice($fat, $numSectors), [self::FREE_SECTOR]), 'What is after the file should be free');
        self::assertLessThan(128, \count($fat) - $numSectors, 'There should not be more FAT sectors than needed');

        $read = static function (int $start, string $what) use ($fat, $sector, &$usage, $numSectors): string {
            $data = '';
            for ($index = $start; self::END_OF_CHAIN !== $index; $index = $fat[$index]) {
                self::assertLessThan($numSectors, $index);
                $usage[$index][] = $what;
                $data .= $sector($index);
            }

            return $data;
        };

        $directory = str_split($read($header['directoryStart'], 'directory'), 128);
        self::assertCount(12, $directory);
        $miniFat = $ints($read($header['miniFatStart'], 'mini FAT'));
        self::assertSame(128 * $header['numMiniFatSectors'], \count($miniFat));

        $tree = [];
        $locations = [];
        $streams = [];
        $miniStream = '';
        $miniUsage = [];
        foreach ($directory as $entry) {
            /** @var array{nameLength: int, type: int, color: int, left: int, right: int, child: int, start: int, size: int} $fields */
            $fields = unpack('vnameLength/Ctype/Ccolor/Vleft/Vright/Vchild/x36/Vstart/Psize', $entry, 64);
            $name = '';
            foreach (str_split(substr($entry, 0, max(2, $fields['nameLength']) - 2), 2) as $char) {
                $name .= $char[0];
                self::assertSame("\0", $char[1] ?? "\0");
            }
            self::assertSame(str_repeat("\0", 64 - max(2, $fields['nameLength']) + 2), substr($entry, max(2, $fields['nameLength']) - 2, 64 - max(2, $fields['nameLength']) + 2));
            self::assertSame('' === $name ? 0 : 2 * \strlen($name) + 2, $fields['nameLength']);
            self::assertSame(str_repeat("\0", 36), substr($entry, 80, 36));

            $id = static fn (int $value): ?int => self::FREE_SECTOR === $value ? null : $value;
            $tree[] = [$name, $fields['type'], $fields['color'], $id($fields['left']), $id($fields['right']), $id($fields['child'])];
            $locations[$name] = [$fields['start'], $fields['size']];

            if (5 === $fields['type']) {
                $miniStream = $read($fields['start'], 'mini stream');
                self::assertSame(0, $fields['size'] % 64);
                self::assertLessThan(512, \strlen($miniStream) - $fields['size'], 'There should not be more mini stream sectors than needed');
                self::assertSame(str_repeat("\0", \strlen($miniStream) - $fields['size']), substr($miniStream, $fields['size']));
                self::assertSame([], array_diff(\array_slice($miniFat, intdiv($fields['size'], 64)), [self::FREE_SECTOR]), 'What is after the mini stream should be free');
                self::assertLessThan(128, \count($miniFat) - intdiv($fields['size'], 64), 'There should not be more mini FAT sectors than needed');
            } elseif (2 === $fields['type'] && $fields['size'] >= 4096) {
                $data = $read($fields['start'], $name);
                self::assertLessThan(512, \strlen($data) - $fields['size']);
                self::assertSame(str_repeat("\0", \strlen($data) - $fields['size']), substr($data, $fields['size']));
                $streams[$name] = substr($data, 0, $fields['size']);
            } elseif (2 === $fields['type']) {
                $data = '';
                $miniSector = $fields['start'];
                for (; \strlen($data) < $fields['size']; $miniSector = $miniFat[$miniSector]) {
                    $miniUsage[] = $miniSector;
                    $data .= substr($miniStream, 64 * $miniSector, 64);
                    self::assertSame(64 * \count($miniUsage), \strlen(implode('', array_map(static fn (int $i): string => substr($miniStream, 64 * $i, 64), $miniUsage))));
                }
                self::assertSame(self::END_OF_CHAIN, 0 === $fields['size'] ? self::END_OF_CHAIN : $miniSector);
                self::assertSame(str_repeat("\0", \strlen($data) - $fields['size']), substr($data, $fields['size']));
                $streams[$name] = substr($data, 0, $fields['size']);
            }
        }
        sort($miniUsage);
        self::assertSame(range(0, intdiv($locations['Root Entry'][1], 64) - 1), $miniUsage, 'Every mini sector should be used once');

        // Every sector has to be used exactly once, and the FAT has to tell what it is used for
        ksort($usage);
        self::assertSame(range(0, $numSectors - 1), array_keys($usage));
        foreach ($usage as $index => $uses) {
            self::assertCount(1, $uses, "The sector {$index} should be used once");
            if ('DIFAT' === $uses[0]) {
                self::assertSame(self::DIFAT_SECTOR, $fat[$index]);
            } elseif ('FAT' === $uses[0]) {
                self::assertSame(self::FAT_SECTOR, $fat[$index]);
            }
        }

        return ['header' => $header, 'tree' => $tree, 'locations' => $locations, 'streams' => $streams];
    }

    private static function data(int $length, string $seed): string
    {
        $data = '';
        for ($i = 0; \strlen($data) < $length; ++$i) {
            $data .= hash('sha512', $seed.$i, true);
        }

        return substr($data, 0, $length);
    }
}
