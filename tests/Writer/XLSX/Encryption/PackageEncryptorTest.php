<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Encryption;

use DOMDocument;
use DOMElement;
use OpenSpout\Reader\Exception\InvalidPasswordException;
use OpenSpout\Reader\XLSX\Encryption\CompoundFile;
use OpenSpout\Reader\XLSX\Encryption\PackageDecryptor;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class PackageEncryptorTest extends TestCase
{
    private const PASSWORD = 'pässwörd €😀';

    private string $tempFolder;

    protected function setUp(): void
    {
        $this->tempFolder = (new TestUsingResource())->getTempFolderPath();
    }

    #[DataProvider('provideEncryptedPackageShouldBeDecryptableCases')]
    public function testEncryptedPackageShouldBeDecryptable(int $packageSize): void
    {
        $package = self::data($packageSize);
        $encryptedFilePath = $this->encrypt($package, self::PASSWORD);

        self::assertTrue(CompoundFile::isCompoundFile($encryptedFilePath));
        $compoundFile = new CompoundFile($encryptedFilePath);
        $encryptedPackage = $compoundFile->readStream('EncryptedPackage', 1000000);
        self::assertSame(AgileEncryptor::getEncryptedPackageSize($packageSize), \strlen($encryptedPackage));
        self::assertSame(8 + 16 * (int) ceil($packageSize / 16), \strlen($encryptedPackage));
        self::assertSame(AgileEncryptor::getEncryptionInfoSize(), \strlen($compoundFile->readStream('EncryptionInfo', 10000)));
        if ($packageSize >= 16) {
            self::assertStringNotContainsString(substr($package, 0, 16), (string) file_get_contents($encryptedFilePath));
        }

        // The decryption also checks the integrity of the encrypted package
        $decryptedFilePath = (new PackageDecryptor())->decrypt($encryptedFilePath, self::PASSWORD, $this->tempFolder);
        self::assertIsString($decryptedFilePath);
        self::assertSame(md5($package), md5_file($decryptedFilePath));
    }

    public static function provideEncryptedPackageShouldBeDecryptableCases(): iterable
    {
        // The encrypted package is stored differently from 4096 bytes, which is 4081 bytes before encryption
        foreach ([0, 1, 15, 16, 17, 4080, 4081, 4095, 4096, 4097, 8191, 8192, 8193, 12288, 20000, 100000] as $packageSize) {
            yield "{$packageSize} bytes" => [$packageSize];
        }
    }

    public function testEncryptShouldRequirePasswordToDecrypt(): void
    {
        $encryptedFilePath = $this->encrypt(self::data(5000), 'secret');

        $this->expectException(InvalidPasswordException::class);

        (new PackageDecryptor())->decrypt($encryptedFilePath, 'Secret', $this->tempFolder);
    }

    public function testEncryptShouldUseSameEncryptionAsExcel(): void
    {
        $encryptedFilePath = $this->encrypt(self::data(5000), 'secret');
        $encryptionInfo = (new CompoundFile($encryptedFilePath))->readStream('EncryptionInfo', 10000);

        self::assertSame('0400040040000000', bin2hex(substr($encryptionInfo, 0, 8)));
        $xml = substr($encryptionInfo, 8);
        $base64 = static fn (int $length): string => '[A-Za-z0-9+/]{'.(4 * intdiv($length, 3)).'}'.[0 => '', 1 => '[A-Za-z0-9+/]{2}==', 2 => '[A-Za-z0-9+/]{3}='][$length % 3];
        self::assertMatchesRegularExpression(
            '#^<\?xml version="1.0" encoding="UTF-8" standalone="yes"\?>'
            .'<encryption xmlns="http://schemas.microsoft.com/office/2006/encryption" xmlns:p="http://schemas.microsoft.com/office/2006/keyEncryptor/password">'
            .'<keyData saltSize="16" blockSize="16" keyBits="256" hashSize="64" cipherAlgorithm="AES" cipherChaining="ChainingModeCBC" hashAlgorithm="SHA512" saltValue="'.$base64(16).'"/>'
            .'<dataIntegrity encryptedHmacKey="'.$base64(64).'" encryptedHmacValue="'.$base64(64).'"/>'
            .'<keyEncryptors><keyEncryptor uri="http://schemas.microsoft.com/office/2006/keyEncryptor/password">'
            .'<p:encryptedKey spinCount="100000" saltSize="16" blockSize="16" keyBits="256" hashSize="64" cipherAlgorithm="AES" cipherChaining="ChainingModeCBC" hashAlgorithm="SHA512"'
            .' saltValue="'.$base64(16).'" encryptedVerifierHashInput="'.$base64(16).'" encryptedVerifierHashValue="'.$base64(64).'" encryptedKeyValue="'.$base64(32).'"/>'
            .'</keyEncryptor></keyEncryptors></encryption>$#',
            $xml
        );
    }

    public function testEncryptShouldNeverUseSameKeys(): void
    {
        $values = [];
        foreach ([1, 2] as $ignored) {
            $compoundFile = new CompoundFile($this->encrypt(self::data(5000), 'secret'));
            $document = new DOMDocument();
            self::assertTrue($document->loadXML(substr($compoundFile->readStream('EncryptionInfo', 10000), 8)));
            foreach (['keyData', 'dataIntegrity', 'encryptedKey'] as $name) {
                $element = $document->getElementsByTagNameNS('*', $name)->item(0);
                self::assertInstanceOf(DOMElement::class, $element);
                foreach ($element->attributes as $attribute) {
                    if (str_starts_with($attribute->name, 'encrypted') || 'saltValue' === $attribute->name) {
                        $values[] = $name.' '.$attribute->name.' '.$attribute->value;
                    }
                }
            }
            $values[] = bin2hex(substr($compoundFile->readStream('EncryptedPackage', 10000), 8));
        }

        // Salts of the package and of the password, encrypted verifier, hash of the verifier, key, HMAC key, HMAC, and package
        self::assertCount(16, $values);
        self::assertCount(16, array_unique($values));
        // The salts of the package and of the password are different too
        self::assertCount(16, array_unique(array_map(static fn (string $value): string => (string) preg_replace('/^.* /', '', $value), $values)));
    }

    public function testEncryptShouldWriteSequentially(): void
    {
        $package = self::data(20000);
        $packageFilePath = $this->write($package);

        // What is sent to the standard output cannot be rewound
        ob_start();
        $stream = fopen('php://output', 'w');
        self::assertIsResource($stream);
        (new PackageEncryptor())->encrypt($packageFilePath, self::PASSWORD, $stream);
        fclose($stream);
        $encryptedFilePath = $this->write((string) ob_get_clean());

        $decryptedFilePath = (new PackageDecryptor())->decrypt($encryptedFilePath, self::PASSWORD, $this->tempFolder);
        self::assertIsString($decryptedFilePath);
        self::assertSame($package, file_get_contents($decryptedFilePath));
    }

    public function testEncryptShouldNotHoldPackageInMemory(): void
    {
        $packageFilePath = $this->write(self::data(3000000));
        $encryptedFilePath = (new TestUsingResource())->getGeneratedResourcePath(uniqid('encrypted_').'.xlsx');
        $stream = fopen($encryptedFilePath, 'w');
        self::assertIsResource($stream);

        memory_reset_peak_usage();
        $memory = memory_get_usage();
        (new PackageEncryptor())->encrypt($packageFilePath, 'secret', $stream);
        $peak = memory_get_peak_usage();
        fclose($stream);

        self::assertGreaterThan(3000000, filesize($encryptedFilePath));
        self::assertLessThan($memory + 1000000, $peak);
    }

    private static function data(int $length): string
    {
        $data = '';
        for ($i = 0; \strlen($data) < $length; ++$i) {
            $data .= hash('sha512', 'package'.$i, true);
        }

        return substr($data, 0, $length);
    }

    private function encrypt(string $package, string $password): string
    {
        $encryptedFilePath = (new TestUsingResource())->getGeneratedResourcePath(uniqid('encrypted_').'.xlsx');
        $stream = fopen($encryptedFilePath, 'w');
        self::assertIsResource($stream);
        (new PackageEncryptor())->encrypt($this->write($package), $password, $stream);
        fclose($stream);

        return $encryptedFilePath;
    }

    private function write(string $content): string
    {
        $path = (new TestUsingResource())->getGeneratedResourcePath(uniqid('package_').'.zip');
        file_put_contents($path, $content);

        return $path;
    }
}
