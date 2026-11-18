<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX\Encryption;

use OpenSpout\Common\Helper\Encryption\Binary;
use OpenSpout\Reader\Exception\EncryptedFileCorruptedException;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CompoundFileTest extends TestCase
{
    public function testIsCompoundFile(): void
    {
        self::assertTrue(CompoundFile::isCompoundFile(TestUsingResource::getResourcePath('encrypted_standard_libreoffice.xlsx')));
        self::assertFalse(CompoundFile::isCompoundFile(TestUsingResource::getResourcePath('one_sheet_with_shared_strings.xlsx')));
        self::assertFalse(CompoundFile::isCompoundFile($this->write("\xD0\xCF\x11\xE0")));
        self::assertTrue(CompoundFile::isCompoundFile($this->write("\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1")));
    }

    #[DataProvider('provideReadStreamCases')]
    public function testReadStream(int $sectorShift, int $length): void
    {
        $data = self::data($length);
        $compoundFile = new CompoundFile($this->write(
            (new CompoundFileBuilder($sectorShift))
                ->addEntry('Before', self::data(100, 'before'))
                ->addEntry('BigBefore', self::data(5000, 'big before'))
                ->addEntry('Data', $data)
                ->addEntry('After', self::data(70, 'after'))
                ->addEntry('BigAfter', self::data(9000, 'big after'))
                ->build()
        ));

        self::assertTrue($compoundFile->hasStream('Data'));
        self::assertSame(md5($data), md5($compoundFile->readStream('Data', $length)));
        self::assertSame(md5($data), md5(implode('', iterator_to_array($compoundFile->streamChunks('Data'), false))));
        self::assertSame(self::data(70, 'after'), $compoundFile->readStream('After', 70));
        self::assertSame(md5(self::data(9000, 'big after')), md5($compoundFile->readStream('BigAfter', 9000)));
    }

    public static function provideReadStreamCases(): iterable
    {
        foreach ([9, 12] as $sectorShift) {
            foreach ([0, 1, 63, 64, 65, 128, 1000, 4095, 4096, 4097, 8192, 10000, 70000] as $length) {
                yield "sector shift {$sectorShift}, {$length} bytes" => [$sectorShift, $length];
            }
        }
    }

    public function testShouldNotHoldStreamsInMemory(): void
    {
        $compoundFile = new CompoundFile($this->write(
            (new CompoundFileBuilder())->addEntry('Small', self::data(200))->addEntry('Big', self::data(3000000))->build()
        ));

        memory_reset_peak_usage();
        $memory = memory_get_usage();
        $length = 0;
        $numChunks = 0;
        foreach ($compoundFile->streamChunks('Big') as $chunk) {
            $length += \strlen($chunk);
            ++$numChunks;
        }
        $peak = memory_get_peak_usage();

        self::assertSame(3000000, $length);
        self::assertSame(5860, $numChunks);
        self::assertLessThan($memory + 1000000, $peak);

        $chunks = iterator_to_array((new CompoundFile($this->write(
            (new CompoundFileBuilder())->addEntry('Small', self::data(200))->build()
        )))->streamChunks('Small'), false);
        self::assertSame([64, 64, 64, 8], array_map(\strlen(...), $chunks));
    }

    /**
     * 109 FAT sectors is the most that can be listed in the header: above that, DIFAT sectors are needed.
     */
    #[DataProvider('provideReadStreamOfFileWithManyFatSectorsCases')]
    public function testReadStreamOfFileWithManyFatSectors(int $length, int $expectedNumFatSectors): void
    {
        $data = self::data($length);
        $file = (new CompoundFileBuilder())->addEntry('First', 'first')->addEntry('Data', $data)->addEntry('Last', 'last')->build();
        self::assertSame($expectedNumFatSectors, Binary::uint32($file, 0x2C));

        $compoundFile = new CompoundFile($this->write($file));
        unset($file);

        $hash = hash_init('md5');
        foreach ($compoundFile->streamChunks('Data') as $chunk) {
            hash_update($hash, $chunk);
        }
        self::assertSame(md5($data), hash_final($hash));
        self::assertSame('last', $compoundFile->readStream('Last', 4));
    }

    public static function provideReadStreamOfFileWithManyFatSectorsCases(): iterable
    {
        return [
            'no DIFAT sector' => [7050000, 109],
            '1 full DIFAT sector' => [15313000, 236],
            '2 DIFAT sectors' => [15440000, 238],
        ];
    }

    public function testShouldFindStreamsByTypeAndExactName(): void
    {
        $compoundFile = new CompoundFile($this->write(
            (new CompoundFileBuilder())
                ->addEntry('Data', 'storage', CompoundFileBuilder::TYPE_STORAGE)
                ->addEntry('DataX', 'longer name')
                ->addEntry('Dat', 'shorter name')
                ->addEntry('Dat1', 'other name')
                ->addEntry('Data', 'expected')
                ->addEntry('Data', 'duplicate')
                ->build()
        ));

        self::assertSame('expected', $compoundFile->readStream('Data', 100));
        self::assertSame('longer name', $compoundFile->readStream('DataX', 100));
        self::assertSame('shorter name', $compoundFile->readStream('Dat', 100));
        self::assertFalse($compoundFile->hasStream('Da'));
        self::assertFalse($compoundFile->hasStream('Root Entry'));
        self::assertFalse($compoundFile->hasStream('Missing'));
    }

    public function testShouldFindStreamsInAnyDirectorySector(): void
    {
        $builder = new CompoundFileBuilder();
        for ($i = 0; $i < 20; ++$i) {
            $builder->addEntry("Stream{$i}", "content {$i}");
        }
        $compoundFile = new CompoundFile($this->write($builder->build()));

        self::assertSame('content 0', $compoundFile->readStream('Stream0', 100));
        self::assertSame('content 19', $compoundFile->readStream('Stream19', 100));
    }

    public function testReadStreamShouldFailForMissingStream(): void
    {
        $compoundFile = new CompoundFile($this->write((new CompoundFileBuilder())->addEntry('Data', 'data')->build()));

        $this->expectException(EncryptedFileCorruptedException::class);
        $this->expectExceptionMessage('The "Missing" stream is missing.');

        $compoundFile->readStream('Missing', 100);
    }

    #[DataProvider('provideReadStreamShouldEnforceMaximumLengthCases')]
    public function testReadStreamShouldEnforceMaximumLength(int $length): void
    {
        $compoundFile = new CompoundFile($this->write((new CompoundFileBuilder())->addEntry('Data', self::data($length))->build()));

        self::assertSame(self::data($length), $compoundFile->readStream('Data', $length));

        $this->expectException(EncryptedFileCorruptedException::class);
        $this->expectExceptionMessage('The "Data" stream is too big.');

        $compoundFile->readStream('Data', $length - 1);
    }

    public static function provideReadStreamShouldEnforceMaximumLengthCases(): iterable
    {
        return [[100], [5000]];
    }

    #[DataProvider('provideShouldRejectInvalidFileCases')]
    public function testShouldRejectInvalidFile(string $content, string $expectedMessage): void
    {
        $this->expectException(EncryptedFileCorruptedException::class);
        $this->expectExceptionMessage($expectedMessage);

        (new CompoundFile($this->write($content)))->readStream('Data', 100000);
    }

    public static function provideShouldRejectInvalidFileCases(): iterable
    {
        $builder = (new CompoundFileBuilder())->addEntry('Mini', self::data(100, 'mini'))->addEntry('Data', self::data(6000));
        $file = $builder->build();
        $numSectors = intdiv(\strlen($file), 512) - 1;
        $numFatEntries = 128;
        // Sectors: FAT (0), directory (1), mini FAT (2), mini stream (3), "Data" (4 to 15)
        self::assertSame(16, $numSectors);

        yield 'not the signature' => ['X'.substr($file, 1), 'The file is not a compound file.'];

        yield 'truncated header' => [substr($file, 0, 511), 'The file is not a compound file.'];

        yield 'invalid sector size' => [self::patch($file, 0x1E, pack('v', 10)), 'The compound file uses an invalid sector size.'];

        yield 'more FAT sectors than sectors' => [self::patch($file, 0x2C, pack('V', $numSectors + 2)), 'The compound file allocation table is bigger than the file.'];

        yield 'as many FAT sectors as sectors' => [self::patch($file, 0x2C, pack('V', $numSectors + 1)), 'The compound file refers to a sector outside of the file.'];

        yield 'FAT sector outside of the file' => [self::patch($file, 0x4C, pack('V', $numSectors)), 'The compound file refers to a sector outside of the file.'];

        yield 'truncated FAT sector' => [substr($file, 0, 1023), 'The compound file refers to a sector outside of the file.'];

        yield 'directory outside of the FAT' => [self::patch($file, 0x30, pack('V', $numFatEntries)), 'The compound file contains an invalid sector chain.'];

        yield 'directory in a free sector' => [self::patch($file, 0x30, pack('V', 0xFFFFFFFF)), 'The compound file contains an invalid sector chain.'];

        yield 'truncated stream' => [substr($file, 0, -1), 'The compound file refers to a sector outside of the file.'];

        yield 'stream ending too early' => [self::patch($file, $builder->fatOffset + 4 * 14, pack('V', CompoundFileBuilder::END_OF_CHAIN)), 'The "Data" stream is truncated.'];

        yield 'stream starting at the end of chain' => [self::patch($file, $builder->directoryOffset + 2 * 128 + 0x74, pack('V', CompoundFileBuilder::END_OF_CHAIN)), 'The "Data" stream is truncated.'];
    }

    public function testLoopingStreamShouldNotGiveMoreThanFileSize(): void
    {
        $builder = (new CompoundFileBuilder())->addEntry('Data', self::data(6000));
        $file = $builder->build();
        // Sectors: FAT (0), directory (1), "Data" (2 to 13)
        self::assertSame(15 * 512, \strlen($file));
        // The stream claims to be huge, and its second sector is made its own successor
        $file = self::patch($file, $builder->directoryOffset + 128 + 0x78, pack('V', 0xFFFFFFFF));
        $file = self::patch($file, $builder->fatOffset + 4 * 3, pack('V', 3));
        $compoundFile = new CompoundFile($this->write($file));

        $length = 0;

        try {
            foreach ($compoundFile->streamChunks('Data') as $chunk) {
                $length += \strlen($chunk);
            }
            self::fail('The stream should not have been read');
        } catch (EncryptedFileCorruptedException $exception) {
            self::assertSame('The compound file contains an invalid sector chain.', $exception->getMessage());
            self::assertSame(\strlen($file), $length);
        }
    }

    public function testStreamSizeShouldBe64BitsLongWith4096BytesSectors(): void
    {
        $data = self::data(10000);
        foreach ([9 => $data, 12 => null] as $sectorShift => $expected) {
            $builder = (new CompoundFileBuilder($sectorShift))->addEntry('Data', $data);
            // The 32 upper bits of the size are set: they are only meaningful with 4096 bytes sectors
            $file = self::patch($builder->build(), $builder->directoryOffset + 128 + 0x7C, pack('V', 1));
            $compoundFile = new CompoundFile($this->write($file));

            try {
                self::assertSame($expected, $compoundFile->readStream('Data', 100000));
            } catch (EncryptedFileCorruptedException $exception) {
                self::assertNull($expected);
                self::assertSame('The "Data" stream is truncated.', $exception->getMessage());
            }
        }
    }

    public function testShouldRejectLoopingSectorChain(): void
    {
        $builder = (new CompoundFileBuilder())->addEntry('Data', self::data(6000));
        $file = $builder->build();
        // The directory is in the sector 1, which is made its own successor
        $compoundFile = new CompoundFile($this->write(self::patch($file, $builder->fatOffset + 4, pack('V', 1))));

        self::assertSame(self::data(6000), $compoundFile->readStream('Data', 6000));

        $this->expectException(EncryptedFileCorruptedException::class);
        $this->expectExceptionMessage('The compound file contains an invalid sector chain.');

        $compoundFile->hasStream('Missing');
    }

    #[DataProvider('provideShouldRejectInvalidMiniStreamCases')]
    public function testShouldRejectInvalidMiniStream(string $content): void
    {
        $compoundFile = new CompoundFile($this->write($content));

        $this->expectException(EncryptedFileCorruptedException::class);
        $this->expectExceptionMessage('The compound file refers to data outside of a sector chain.');

        $compoundFile->readStream('Mini', 100000);
    }

    public static function provideShouldRejectInvalidMiniStreamCases(): iterable
    {
        $builder = (new CompoundFileBuilder())->addEntry('Mini', self::data(100, 'mini'))->addEntry('Data', self::data(6000));
        $file = $builder->build();

        yield 'mini sector outside of the mini stream' => [self::patch($file, $builder->directoryOffset + 128 + 0x74, pack('V', 8))];

        yield 'mini sector chain ending too early' => [self::patch($file, $builder->miniFatOffset, pack('V', CompoundFileBuilder::END_OF_CHAIN))];

        yield 'mini sector outside of the mini FAT' => [self::patch($file, $builder->miniFatOffset, pack('V', 128))];

        yield 'no mini stream' => [self::patch($file, $builder->directoryOffset + 0x74, pack('V', CompoundFileBuilder::END_OF_CHAIN))];
    }

    public function testShouldReadLastMiniSectorOfMiniStreamSector(): void
    {
        // 8 mini sectors fit in a sector: the 8th one is the last one that can be read from the first sector
        $builder = (new CompoundFileBuilder())->addEntry('Mini', self::data(7 * 64 + 10));
        $file = $builder->build();
        $compoundFile = new CompoundFile($this->write($file));

        self::assertSame(self::data(7 * 64 + 10), $compoundFile->readStream('Mini', 1000));
    }

    private static function data(int $length, string $seed = 'data'): string
    {
        $data = '';
        for ($i = 0; \strlen($data) < $length; ++$i) {
            $data .= hash('sha512', $seed.$i, true);
        }

        return substr($data, 0, $length);
    }

    private static function patch(string $content, int $offset, string $replacement): string
    {
        return substr_replace($content, $replacement, $offset, \strlen($replacement));
    }

    private function write(string $content): string
    {
        $path = (new TestUsingResource())->getGeneratedResourcePath(uniqid('compound_file_').'.cfb');
        file_put_contents($path, $content);

        return $path;
    }
}
