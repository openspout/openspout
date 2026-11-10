<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX\Encryption;

use OpenSpout\Common\Helper\Encryption\Binary;
use OpenSpout\Reader\Exception\EncryptedFileCorruptedException;
use OpenSpout\Reader\Exception\InvalidPasswordException;
use OpenSpout\Reader\Exception\PasswordRequiredException;
use OpenSpout\Reader\Exception\ReaderException;
use OpenSpout\Reader\Exception\UnsupportedEncryptionException;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class PackageDecryptorTest extends TestCase
{
    private const PASSWORD = 'pässwörd €😀';

    private string $tempFolder;

    protected function setUp(): void
    {
        $this->tempFolder = (new TestUsingResource())->getTempFolderPath();
    }

    #[DataProvider('providePackageLengthCases')]
    public function testDecryptAgile(int $length): void
    {
        $package = self::data($length);

        self::assertSame($package, $this->decrypt(TestEncryptor::agile($package, self::PASSWORD), self::PASSWORD));
    }

    #[DataProvider('providePackageLengthCases')]
    public function testDecryptStandard(int $length): void
    {
        $package = self::data($length);

        self::assertSame($package, $this->decrypt(TestEncryptor::standard($package, self::PASSWORD), self::PASSWORD));
    }

    public static function providePackageLengthCases(): iterable
    {
        // What is stored is 8 bytes bigger than the package, and is stored differently from 4096 bytes
        foreach ([0, 1, 15, 16, 17, 4000, 4072, 4073, 4088, 4095, 4096, 4097, 8191, 8192, 8193, 20000] as $length) {
            yield "{$length} bytes" => [$length];
        }
    }

    /**
     * @param array{keyBits?: int, hashAlgorithm?: string, saltSize?: int}                  $keyData
     * @param array{keyBits?: int, hashAlgorithm?: string, saltSize?: int, spinCount?: int} $encryptedKey
     */
    #[DataProvider('provideDecryptAgileProfileCases')]
    public function testDecryptAgileProfile(array $keyData, array $encryptedKey): void
    {
        $package = self::data(5000);

        self::assertSame($package, $this->decrypt(TestEncryptor::agile($package, self::PASSWORD, $keyData, $encryptedKey), self::PASSWORD));
    }

    public static function provideDecryptAgileProfileCases(): iterable
    {
        foreach ([128, 192, 256] as $keyBits) {
            foreach (['SHA1', 'SHA256', 'SHA384', 'SHA512'] as $hashAlgorithm) {
                $profile = ['keyBits' => $keyBits, 'hashAlgorithm' => $hashAlgorithm];

                yield "AES-{$keyBits} {$hashAlgorithm}" => [$profile, $profile];
            }
        }

        yield 'stronger password protection' => [['keyBits' => 128, 'hashAlgorithm' => 'SHA1'], ['keyBits' => 256, 'hashAlgorithm' => 'SHA512']];

        yield 'weaker password protection' => [['keyBits' => 256, 'hashAlgorithm' => 'SHA512'], ['keyBits' => 128, 'hashAlgorithm' => 'SHA1']];

        yield 'short salts' => [['saltSize' => 8], ['saltSize' => 9]];

        yield 'long salts' => [['saltSize' => 24], ['saltSize' => 31]];

        yield 'salts of different sizes' => [['saltSize' => 32], ['saltSize' => 16]];

        yield 'no spin' => [[], ['spinCount' => 0]];

        yield 'usual spin count' => [[], ['spinCount' => 100000]];
    }

    #[DataProvider('provideDecryptStandardProfileCases')]
    public function testDecryptStandardProfile(int $algorithm, int $versionMajor): void
    {
        $package = self::data(5000);

        self::assertSame($package, $this->decrypt(TestEncryptor::standard($package, self::PASSWORD, $algorithm, $versionMajor), self::PASSWORD));
    }

    public static function provideDecryptStandardProfileCases(): iterable
    {
        return [
            'AES-128, version 2.2' => [0x660E, 2],
            'AES-192, version 3.2' => [0x660F, 3],
            'AES-256, version 4.2' => [0x6610, 4],
        ];
    }

    public function testDecryptShouldSupportEveryContainerLayout(): void
    {
        $package = self::data(10000);
        [$encryptionInfo, $encryptedPackage] = TestEncryptor::agile($package, self::PASSWORD);
        $file = (new CompoundFileBuilder(12))
            ->addEntry("\x06DataSpaces", '', CompoundFileBuilder::TYPE_STORAGE)
            ->addEntry('EncryptedPackage', $encryptedPackage)
            ->addEntry('EncryptionInfo', $encryptionInfo)
            ->build()
        ;

        self::assertSame($package, $this->decryptFile($file, self::PASSWORD));
    }

    public function testDecryptShouldCreateFileInTempFolder(): void
    {
        $decryptedFilePath = (new PackageDecryptor())->decrypt(
            $this->write($this->container(TestEncryptor::standard('package', 'secret'))),
            'secret',
            $this->tempFolder
        );

        self::assertIsString($decryptedFilePath);
        self::assertSame($this->tempFolder, \dirname($decryptedFilePath));
        self::assertStringStartsWith('openspout_xlsx_', basename($decryptedFilePath));
        self::assertSame('package', file_get_contents($decryptedFilePath));
    }

    /**
     * @param array{string, string} $streams
     */
    #[DataProvider('provideEncryptedStreamsCases')]
    public function testDecryptShouldRejectWrongPassword(array $streams): void
    {
        $this->expectFailure(InvalidPasswordException::class, 'The password is incorrect.');

        $this->decrypt($streams, 'Secret');
    }

    /**
     * @param array{string, string} $streams
     */
    #[DataProvider('provideEncryptedStreamsCases')]
    public function testDecryptShouldRequirePassword(array $streams): void
    {
        $this->expectFailure(PasswordRequiredException::class, 'The file is encrypted: a password is required to read it.');

        $this->decrypt($streams, null);
    }

    /**
     * @param array{string, string} $streams
     */
    #[DataProvider('provideEncryptedStreamsCases')]
    public function testDecryptShouldRejectEmptyPassword(array $streams): void
    {
        $this->expectFailure(InvalidPasswordException::class, 'The password is incorrect.');

        $this->decrypt($streams, '');
    }

    public static function provideEncryptedStreamsCases(): iterable
    {
        return [
            'agile' => [TestEncryptor::agile('package', 'secret')],
            'standard' => [TestEncryptor::standard('package', 'secret')],
        ];
    }

    public function testDecryptAgileShouldUseDefaultPasswordOfExcel(): void
    {
        self::assertSame('package', $this->decrypt(TestEncryptor::agile('package', 'VelvetSweatshop'), null));
    }

    public function testDecryptStandardShouldUseDefaultPasswordOfExcel(): void
    {
        self::assertSame('package', $this->decrypt(TestEncryptor::standard('package', 'VelvetSweatshop'), null));
    }

    public function testDecryptShouldRejectInvalidUtf8Password(): void
    {
        $this->expectFailure(InvalidPasswordException::class, 'The password must be a valid UTF-8 string.');

        $this->decrypt(TestEncryptor::agile('package', 'secret'), "\xE9");
    }

    public function testDecryptShouldIgnoreFilesThatAreNotEncrypted(): void
    {
        [$encryptionInfo, $encryptedPackage] = TestEncryptor::agile('package', 'secret');
        $decryptor = new PackageDecryptor();

        foreach ([['EncryptionInfo', $encryptionInfo], ['EncryptedPackage', $encryptedPackage], ['Workbook', 'legacy file']] as [$name, $data]) {
            $file = $this->write((new CompoundFileBuilder())->addEntry($name, $data)->build());

            self::assertNull($decryptor->decrypt($file, 'secret', $this->tempFolder));
        }
        self::assertSame([], $this->tempFiles());
    }

    #[DataProvider('provideDecryptShouldRejectUnsupportedVersionCases')]
    public function testDecryptShouldRejectUnsupportedVersion(int $versionMajor, int $versionMinor): void
    {
        [$encryptionInfo, $encryptedPackage] = TestEncryptor::agile('package', 'secret');

        $this->expectFailure(UnsupportedEncryptionException::class, "The encryption version {$versionMajor}.{$versionMinor} is not supported.");

        $this->decrypt([pack('vv', $versionMajor, $versionMinor).substr($encryptionInfo, 4), $encryptedPackage], 'secret');
    }

    public static function provideDecryptShouldRejectUnsupportedVersionCases(): iterable
    {
        return [
            'RC4' => [1, 1],
            'RC4 CryptoAPI' => [2, 1],
            'extensible' => [4, 3],
            [1, 2],
            [5, 2],
            [3, 4],
            [5, 4],
            [4, 5],
        ];
    }

    public function testDecryptShouldRejectVersionOnly(): void
    {
        [, $encryptedPackage] = TestEncryptor::agile('package', 'secret');

        $this->expectFailure(UnsupportedEncryptionException::class, 'The encryption version 9.9 is not supported.');

        $this->decrypt([pack('vv', 9, 9), $encryptedPackage], 'secret');
    }

    /**
     * @param array{string, string} $streams
     */
    #[DataProvider('provideDecryptShouldRejectCorruptedFileCases')]
    public function testDecryptShouldRejectCorruptedFile(array $streams, string $expectedMessage): void
    {
        $this->expectFailure(EncryptedFileCorruptedException::class, $expectedMessage);

        $this->decrypt($streams, 'secret');
    }

    public static function provideDecryptShouldRejectCorruptedFileCases(): iterable
    {
        $package = self::data(5000);
        $malformed = 'The encryption information is malformed.';
        $truncated = 'The encrypted package is truncated.';
        $altered = 'The encrypted package has been altered.';

        [$encryptionInfo, $encryptedPackage] = TestEncryptor::agile($package, 'secret');

        yield 'no version' => [['', $encryptedPackage], $malformed];

        yield 'truncated version' => [[pack('v', 9)."\x09", $encryptedPackage], $malformed];

        yield 'agile: version only' => [[substr($encryptionInfo, 0, 4), $encryptedPackage], $malformed];

        yield 'encryption information too big' => [[$encryptionInfo.str_repeat(' ', 65537 - \strlen($encryptionInfo)), $encryptedPackage], 'The "EncryptionInfo" stream is too big.'];

        yield 'agile: no XML' => [[substr($encryptionInfo, 0, 8), $encryptedPackage], $malformed];

        yield 'agile: malformed XML' => [[substr($encryptionInfo, 0, -1), $encryptedPackage], $malformed];

        yield 'agile: XML with a DOCTYPE' => [[str_replace('<encryption ', '<!DOCTYPE encryption><encryption ', $encryptionInfo), $encryptedPackage], $malformed];

        yield 'agile: no keyData' => [[str_replace('<keyData', '<keyDatum', $encryptionInfo), $encryptedPackage], 'The encryption information has no "keyData" element.'];

        yield 'agile: keyData in another namespace' => [[str_replace('<keyData', '<p:keyData', $encryptionInfo), $encryptedPackage], 'The encryption information has no "keyData" element.'];

        yield 'agile: no dataIntegrity' => [[str_replace('<dataIntegrity', '<dataIntegrities', $encryptionInfo), $encryptedPackage], 'The encryption information has no "dataIntegrity" element.'];

        foreach (['saltValue', 'encryptedHmacKey', 'encryptedHmacValue', 'encryptedVerifierHashInput', 'encryptedVerifierHashValue', 'encryptedKeyValue'] as $attribute) {
            $message = "The encryption information has no valid \"{$attribute}\" attribute.";

            yield "agile: no {$attribute}" => [[str_replace(" {$attribute}=", " x{$attribute}=", $encryptionInfo), $encryptedPackage], $message];

            yield "agile: {$attribute} is not base64" => [[str_replace(" {$attribute}=\"", " {$attribute}=\"!", $encryptionInfo), $encryptedPackage], $message];
        }

        yield 'agile: encrypted value that is not made of blocks' => [
            [(string) preg_replace('/ encryptedKeyValue="[^"]+"/', ' encryptedKeyValue="'.base64_encode(str_repeat('x', 33)).'"', $encryptionInfo), $encryptedPackage],
            'The encrypted data is not a whole number of AES blocks.',
        ];

        yield 'agile: no size' => [[$encryptionInfo, substr($encryptedPackage, 0, 7)], $truncated];

        yield 'agile: truncated package' => [[$encryptionInfo, substr($encryptedPackage, 0, -1)], $truncated];

        yield 'agile: truncated package, in the mini stream' => [[$encryptionInfo, substr($encryptedPackage, 0, 4000)], $truncated];

        yield 'agile: package bigger than what is stored' => [[$encryptionInfo, pack('P', 5009).substr($encryptedPackage, 8)], $truncated];

        yield 'agile: package smaller than what is stored' => [[$encryptionInfo, pack('P', 4999).substr($encryptedPackage, 8)], $altered];

        yield 'agile: altered first segment' => [[$encryptionInfo, substr_replace($encryptedPackage, 'X', 8, 1)], $altered];

        yield 'agile: altered last segment' => [[$encryptionInfo, substr_replace($encryptedPackage, 'X', -1, 1)], $altered];

        yield 'agile: added data' => [[$encryptionInfo, $encryptedPackage.'X'], $altered];

        yield 'agile: added segment' => [[$encryptionInfo, $encryptedPackage.str_repeat('X', 5000)], $altered];

        [$encryptionInfo, $encryptedPackage] = TestEncryptor::standard($package, 'secret');
        $headerSize = Binary::uint32($encryptionInfo, 8);
        $verifierOffset = 12 + $headerSize;

        yield 'standard: no header' => [[substr($encryptionInfo, 0, 8), $encryptedPackage], $malformed];

        yield 'standard: header too small' => [
            [substr($encryptionInfo, 0, 8).pack('V', 11).substr($encryptionInfo, 12, 11).substr($encryptionInfo, $verifierOffset), $encryptedPackage],
            $malformed,
        ];

        yield 'standard: header bigger than the stream' => [[substr_replace($encryptionInfo, pack('V', 0xFFFFFFFF), 8, 4), $encryptedPackage], $malformed];

        yield 'standard: truncated verifier' => [[substr($encryptionInfo, 0, -1), $encryptedPackage], $malformed];

        yield 'standard: invalid salt size' => [[substr_replace($encryptionInfo, pack('V', 32), $verifierOffset, 4), $encryptedPackage], $malformed];

        yield 'standard: invalid verifier hash size' => [[substr_replace($encryptionInfo, pack('V', 32), $verifierOffset + 36, 4), $encryptedPackage], $malformed];

        yield 'standard: no size' => [[$encryptionInfo, substr($encryptedPackage, 0, 7)], $truncated];

        yield 'standard: truncated package' => [[$encryptionInfo, substr($encryptedPackage, 0, -1)], $truncated];

        yield 'standard: package bigger than what is stored' => [[$encryptionInfo, pack('P', 5009).substr($encryptedPackage, 8)], $truncated];
    }

    /**
     * @param array{string, string} $streams
     */
    #[DataProvider('provideDecryptShouldRejectUnsupportedEncryptionCases')]
    public function testDecryptShouldRejectUnsupportedEncryption(array $streams, string $expectedMessage): void
    {
        $this->expectFailure(UnsupportedEncryptionException::class, $expectedMessage);

        $this->decrypt($streams, 'secret');
    }

    public static function provideDecryptShouldRejectUnsupportedEncryptionCases(): iterable
    {
        $unsupported = 'Only AES in CBC mode, with a SHA-1 or SHA-2 hash, is supported for files using the agile encryption.';
        [$encryptionInfo, $encryptedPackage] = TestEncryptor::agile('package', 'secret', ['keyBits' => 128, 'hashAlgorithm' => 'SHA1'], ['keyBits' => 128, 'hashAlgorithm' => 'SHA1']);

        yield 'agile: no password' => [[str_replace('p:encryptedKey', 'c:encryptedKey xmlns:c="http://schemas.microsoft.com/office/2006/keyEncryptor/certificate"', $encryptionInfo), $encryptedPackage], 'Only files encrypted with a password are supported.'];

        // The first occurrence is the one of the package, the second one is the one of the password
        foreach (['package' => 1, 'password' => 2] as $target => $occurrence) {
            $replacements = [
                'cipher' => ['cipherAlgorithm="AES"', 'cipherAlgorithm="DES"'],
                'chaining' => ['cipherChaining="ChainingModeCBC"', 'cipherChaining="ChainingModeCFB"'],
                'hash' => ['hashAlgorithm="SHA1"', 'hashAlgorithm="MD5"'],
                'lower case hash' => ['hashAlgorithm="SHA1"', 'hashAlgorithm="sha1"'],
                'key size' => ['keyBits="128"', 'keyBits="64"'],
                'big key size' => ['keyBits="128"', 'keyBits="512"'],
            ];
            foreach ($replacements as $name => [$search, $replacement]) {
                $parts = explode($search, $encryptionInfo);
                $parts[$occurrence - 1] .= $replacement.$parts[$occurrence];
                unset($parts[$occurrence]);

                yield "agile: unsupported {$name} for the {$target}" => [[implode($search, $parts), $encryptedPackage], $unsupported];
            }
        }

        $outOfRange = 'The spin count of the encryption is out of range.';

        yield 'agile: negative spin count' => [[str_replace('spinCount="100"', 'spinCount="-1"', $encryptionInfo), $encryptedPackage], $outOfRange];

        yield 'agile: spin count too high' => [[str_replace('spinCount="100"', 'spinCount="10000001"', $encryptionInfo), $encryptedPackage], $outOfRange];

        yield 'standard: RC4' => [TestEncryptor::standard('package', 'secret', 0x6801), 'Only AES is supported for files using the standard encryption.'];
    }

    public function testAgileShouldAcceptHighestSpinCountOfOffice(): void
    {
        [$encryptionInfo] = TestEncryptor::agile('package', 'secret');

        // Hashing that much is too slow to be tested: only the fact that the file is accepted is
        $this->expectNotToPerformAssertions();

        new AgileCipher(str_replace('spinCount="100"', 'spinCount="'.AgileCipher::MAX_SPIN_COUNT.'"', $encryptionInfo));
    }

    public function testDecryptAgileWithTrailingData(): void
    {
        $package = self::data(5000);

        self::assertSame($package, $this->decrypt(TestEncryptor::agile($package, 'secret', [], [], self::data(9000, 'trailing')), 'secret'));
    }

    public function testDecryptStandardWithTrailingData(): void
    {
        $package = self::data(5000);
        [$encryptionInfo, $encryptedPackage] = TestEncryptor::standard($package, 'secret');

        self::assertSame($package, $this->decrypt([$encryptionInfo, $encryptedPackage.self::data(9000, 'trailing')], 'secret'));
    }

    public function testDecryptStandardWithSmallestHeader(): void
    {
        [$encryptionInfo, $encryptedPackage] = TestEncryptor::standard('package', 'secret');
        $verifierOffset = 12 + Binary::uint32($encryptionInfo, 8);
        // Only the flags, the extra size and the algorithm are kept in the header
        $encryptionInfo = substr($encryptionInfo, 0, 8).pack('V', 12).substr($encryptionInfo, 12, 12).substr($encryptionInfo, $verifierOffset);

        self::assertSame('package', $this->decrypt([$encryptionInfo, $encryptedPackage], 'secret'));
    }

    public function testDecryptShouldNotHoldPackageInMemory(): void
    {
        $file = $this->write($this->container(TestEncryptor::agile(self::data(3000000), 'secret')));

        memory_reset_peak_usage();
        $memory = memory_get_usage();
        $decryptedFilePath = (new PackageDecryptor())->decrypt($file, 'secret', $this->tempFolder);
        $peak = memory_get_peak_usage();

        self::assertIsString($decryptedFilePath);
        self::assertSame(3000000, filesize($decryptedFilePath));
        self::assertLessThan($memory + 1000000, $peak);
    }

    private static function data(int $length, string $seed = 'data'): string
    {
        $data = '';
        for ($i = 0; \strlen($data) < $length; ++$i) {
            $data .= hash('sha512', $seed.$i, true);
        }

        return substr($data, 0, $length);
    }

    /**
     * @param class-string<ReaderException> $exception
     */
    private function expectFailure(string $exception, string $message): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);
    }

    /**
     * @param array{string, string} $streams Content of the "EncryptionInfo" and of the "EncryptedPackage" streams
     */
    private function container(array $streams): string
    {
        return (new CompoundFileBuilder())
            ->addEntry('EncryptionInfo', $streams[0])
            ->addEntry('EncryptedPackage', $streams[1])
            ->build()
        ;
    }

    /**
     * @param array{string, string} $streams Content of the "EncryptionInfo" and of the "EncryptedPackage" streams
     */
    private function decrypt(array $streams, ?string $password): string
    {
        return $this->decryptFile($this->container($streams), $password);
    }

    private function decryptFile(string $content, ?string $password): string
    {
        try {
            $decryptedFilePath = (new PackageDecryptor())->decrypt($this->write($content), $password, $this->tempFolder);
        } catch (ReaderException $exception) {
            self::assertSame([], $this->tempFiles(), 'No decrypted file should be left behind');

            throw $exception;
        }
        self::assertIsString($decryptedFilePath);
        self::assertSame([$decryptedFilePath], $this->tempFiles());

        $decrypted = file_get_contents($decryptedFilePath);
        self::assertIsString($decrypted);

        return $decrypted;
    }

    /**
     * @return list<string>
     */
    private function tempFiles(): array
    {
        $files = glob($this->tempFolder.\DIRECTORY_SEPARATOR.'*');
        self::assertIsArray($files);

        return $files;
    }

    private function write(string $content): string
    {
        $path = (new TestUsingResource())->getGeneratedResourcePath(uniqid('encrypted_').'.xlsx');
        file_put_contents($path, $content);

        return $path;
    }
}
