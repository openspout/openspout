<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX\Encryption;

use OpenSpout\Common\Helper\Encryption\Binary;
use OpenSpout\Reader\Exception\EncryptedFileCorruptedException;
use OpenSpout\Reader\Exception\UnsupportedEncryptionException;

/**
 * "Standard" encryption, as written by Excel 2007 and LibreOffice: AES in ECB mode, with a SHA-1 based key.
 *
 * @see https://learn.microsoft.com/openspecs/office_file_formats/ms-offcrypto (2.3.4.5 to 2.3.4.9)
 *
 * @internal
 */
final class StandardCipher implements CipherInterface
{
    private const SPIN_COUNT = 50000;
    private const SALT_SIZE = 16;
    private const VERIFIER_SIZE = 16;
    private const VERIFIER_HASH_SIZE = 20;
    private const ENCRYPTED_VERIFIER_HASH_SIZE = 32;

    /**
     * Key sizes, in bits, of the supported algorithms.
     */
    private const ALGORITHMS = [
        0x660E => 128,
        0x660F => 192,
        0x6610 => 256,
    ];

    private readonly int $keyBits;
    private readonly string $salt;
    private readonly string $encryptedVerifier;
    private readonly string $encryptedVerifierHash;
    private string $key = '';

    /**
     * @param string $encryptionInfo Content of the "EncryptionInfo" stream
     *
     * @throws EncryptedFileCorruptedException
     * @throws UnsupportedEncryptionException
     */
    public function __construct(string $encryptionInfo)
    {
        // Version (4 bytes), flags (4 bytes), header size (4 bytes), header, verifier
        $headerSize = Binary::uint32(str_pad($encryptionInfo, 12, "\0"), 8);
        $verifier = substr($encryptionInfo, 12 + $headerSize);
        $verifierSize = 4 + self::SALT_SIZE + self::VERIFIER_SIZE + 4 + self::ENCRYPTED_VERIFIER_HASH_SIZE;
        if ($headerSize < 12
            || \strlen($verifier) < $verifierSize
            || self::SALT_SIZE !== Binary::uint32($verifier, 0)
            || self::VERIFIER_HASH_SIZE !== Binary::uint32($verifier, 4 + self::SALT_SIZE + self::VERIFIER_SIZE)
        ) {
            throw new EncryptedFileCorruptedException('The encryption information is malformed.');
        }

        // Header: flags (4 bytes), extra size (4 bytes), algorithm (4 bytes)...
        $this->keyBits = self::ALGORITHMS[Binary::uint32($encryptionInfo, 12 + 8)]
            ?? throw new UnsupportedEncryptionException('Only AES is supported for files using the standard encryption.');
        $this->salt = substr($verifier, 4, self::SALT_SIZE);
        $this->encryptedVerifier = substr($verifier, 4 + self::SALT_SIZE, self::VERIFIER_SIZE);
        $this->encryptedVerifierHash = substr($verifier, $verifierSize - self::ENCRYPTED_VERIFIER_HASH_SIZE, self::ENCRYPTED_VERIFIER_HASH_SIZE);
    }

    public function unlock(string $password): bool
    {
        $hash = Binary::hashPassword('sha1', $this->salt, $password, self::SPIN_COUNT);
        $hash = str_pad(sha1($hash.pack('V', 0), true), 64, "\0");
        $key = Binary::fit(
            sha1($hash ^ str_repeat("\x36", 64), true).sha1($hash ^ str_repeat("\x5C", 64), true),
            intdiv($this->keyBits, 8)
        );

        $verifier = Aes::decrypt('ecb', $this->keyBits, $key, $this->encryptedVerifier);
        $verifierHash = Aes::decrypt('ecb', $this->keyBits, $key, $this->encryptedVerifierHash);
        if (!hash_equals(sha1($verifier, true), substr($verifierHash, 0, self::VERIFIER_HASH_SIZE))) {
            return false;
        }
        $this->key = $key;

        return true;
    }

    public function decryptSegment(int $index, string $data): string
    {
        return Aes::decrypt('ecb', $this->keyBits, $this->key, $data);
    }

    /**
     * The standard encryption has no integrity check.
     */
    public function updateIntegrity(string $data): void {}

    public function isIntact(): bool
    {
        return true;
    }
}
