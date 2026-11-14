<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Encryption;

use HashContext;
use OpenSpout\Common\Helper\Encryption\Binary;
use SensitiveParameter;

/**
 * Encrypts a package the way Excel 2010 and later do: "agile" encryption, using AES-256 in CBC mode
 * and SHA-512, where the package is encrypted by segments of 4096 bytes and protected by an HMAC.
 *
 * @see https://learn.microsoft.com/openspecs/office_file_formats/ms-offcrypto (2.3.4.10 to 2.3.4.15)
 *
 * @internal
 */
final class AgileEncryptor
{
    public const SEGMENT_SIZE = 4096;

    private const KEY_BITS = 256;
    private const KEY_SIZE = 32;
    private const HASH_ALGORITHM = 'sha512';
    private const HASH_SIZE = 64;
    private const SPIN_COUNT = 100000;
    private const SALT_SIZE = 16;
    private const BLOCK_SIZE = 16;

    private const BLOCK_KEY_VERIFIER_INPUT = "\xFE\xA7\xD2\x76\x3B\x4B\x9E\x79";
    private const BLOCK_KEY_VERIFIER_VALUE = "\xD7\xAA\x0F\x6D\x30\x61\x34\x4E";
    private const BLOCK_KEY_KEY_VALUE = "\x14\x6E\x0B\xE7\xAB\xAC\xD0\xD6";
    private const BLOCK_KEY_HMAC_KEY = "\x5F\xB2\xAD\x01\x0C\xB9\xE1\xF6";
    private const BLOCK_KEY_HMAC_VALUE = "\xA0\x67\x7F\x02\xB2\x2C\x84\x33";

    private readonly string $packageSalt;
    private readonly string $packageKey;
    private readonly string $passwordSalt;
    private readonly string $encryptedVerifierInput;
    private readonly string $encryptedVerifierValue;
    private readonly string $encryptedKeyValue;
    private readonly string $hmacKey;
    private readonly HashContext $hmac;

    /**
     * @param string $password UTF-16LE encoded password
     */
    public function __construct(#[SensitiveParameter] string $password)
    {
        $this->packageSalt = random_bytes(self::SALT_SIZE);
        $this->packageKey = random_bytes(self::KEY_SIZE);
        $this->passwordSalt = random_bytes(self::SALT_SIZE);
        $this->hmacKey = random_bytes(self::HASH_SIZE);
        $this->hmac = hash_init(self::HASH_ALGORITHM, HASH_HMAC, $this->hmacKey);

        // The key of the package is itself encrypted with keys that are derived from the password
        $passwordHash = Binary::hashPassword(self::HASH_ALGORITHM, $this->passwordSalt, $password, self::SPIN_COUNT);
        $encrypt = function (string $blockKey, string $data) use ($passwordHash): string {
            $key = Binary::fit(hash(self::HASH_ALGORITHM, $passwordHash.$blockKey, true), self::KEY_SIZE);

            return self::aesEncrypt($key, $data, $this->passwordSalt);
        };

        // The verifier, stored next to its hash, is what allows to tell whether a password is the right one
        $verifier = random_bytes(self::SALT_SIZE);
        $this->encryptedVerifierInput = $encrypt(self::BLOCK_KEY_VERIFIER_INPUT, $verifier);
        $this->encryptedVerifierValue = $encrypt(self::BLOCK_KEY_VERIFIER_VALUE, hash(self::HASH_ALGORITHM, $verifier, true));
        $this->encryptedKeyValue = $encrypt(self::BLOCK_KEY_KEY_VALUE, $this->packageKey);
    }

    public static function getEncryptedPackageSize(int $packageSize): int
    {
        // The encrypted package starts with the size of the package: every segment is then padded to the size of an AES block
        return 8 + self::BLOCK_SIZE * intdiv($packageSize + self::BLOCK_SIZE - 1, self::BLOCK_SIZE);
    }

    public static function getEncryptionInfoSize(): int
    {
        return \strlen(self::buildEncryptionInfo(
            str_repeat("\0", self::SALT_SIZE),
            str_repeat("\0", self::SALT_SIZE),
            str_repeat("\0", self::BLOCK_SIZE),
            str_repeat("\0", self::HASH_SIZE),
            str_repeat("\0", self::KEY_SIZE),
            str_repeat("\0", self::HASH_SIZE),
            str_repeat("\0", self::HASH_SIZE),
        ));
    }

    /**
     * Returns the beginning of the encrypted package, which is the size of the package.
     */
    public function start(int $packageSize): string
    {
        $header = pack('P', $packageSize);
        hash_update($this->hmac, $header);

        return $header;
    }

    /**
     * Encrypts one segment of the package: they all are SEGMENT_SIZE bytes long, except the last one.
     *
     * @param int $index Zero based index of the segment
     */
    public function encryptSegment(int $index, string $segment): string
    {
        $encrypted = $this->encryptWithPackageKey(pack('V', $index), $segment);
        hash_update($this->hmac, $encrypted);

        return $encrypted;
    }

    /**
     * Returns the content of the "EncryptionInfo" stream.
     * It has to be called once the whole package has been encrypted, as it holds what allows to check its integrity.
     */
    public function getEncryptionInfo(): string
    {
        return self::buildEncryptionInfo(
            $this->packageSalt,
            $this->passwordSalt,
            $this->encryptedVerifierInput,
            $this->encryptedVerifierValue,
            $this->encryptedKeyValue,
            $this->encryptWithPackageKey(self::BLOCK_KEY_HMAC_KEY, $this->hmacKey),
            $this->encryptWithPackageKey(self::BLOCK_KEY_HMAC_VALUE, hash_final($this->hmac, true)),
        );
    }

    /**
     * Everything encrypted with the key of the package uses an initialization vector derived from a "block key".
     */
    private function encryptWithPackageKey(string $blockKey, string $data): string
    {
        $iv = Binary::fit(hash(self::HASH_ALGORITHM, $this->packageSalt.$blockKey, true), self::BLOCK_SIZE);

        return self::aesEncrypt($this->packageKey, $data, $iv);
    }

    private static function aesEncrypt(string $key, string $data, string $iv): string
    {
        // The data is padded with zeros, up to the next AES block
        $padded = str_pad($data, self::BLOCK_SIZE * intdiv(\strlen($data) + self::BLOCK_SIZE - 1, self::BLOCK_SIZE), "\0");
        $encrypted = openssl_encrypt($padded, 'aes-'.self::KEY_BITS.'-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
        \assert(false !== $encrypted);

        return $encrypted;
    }

    private static function buildEncryptionInfo(
        string $packageSalt,
        string $passwordSalt,
        string $encryptedVerifierInput,
        string $encryptedVerifierValue,
        string $encryptedKeyValue,
        string $encryptedHmacKey,
        string $encryptedHmacValue,
    ): string {
        $algorithms = ' blockSize="'.self::BLOCK_SIZE.'" keyBits="'.self::KEY_BITS.'" hashSize="'.self::HASH_SIZE.'"'
            .' cipherAlgorithm="AES" cipherChaining="ChainingModeCBC" hashAlgorithm="SHA512"';

        // Version 4.4, followed by a reserved value
        return pack('vvV', 4, 4, 0x40)
            .'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<encryption xmlns="http://schemas.microsoft.com/office/2006/encryption" xmlns:p="http://schemas.microsoft.com/office/2006/keyEncryptor/password">'
            .'<keyData saltSize="'.\strlen($packageSalt).'"'.$algorithms.' saltValue="'.base64_encode($packageSalt).'"/>'
            .'<dataIntegrity encryptedHmacKey="'.base64_encode($encryptedHmacKey).'" encryptedHmacValue="'.base64_encode($encryptedHmacValue).'"/>'
            .'<keyEncryptors><keyEncryptor uri="http://schemas.microsoft.com/office/2006/keyEncryptor/password">'
            .'<p:encryptedKey spinCount="'.self::SPIN_COUNT.'" saltSize="'.\strlen($passwordSalt).'"'.$algorithms
            .' saltValue="'.base64_encode($passwordSalt).'"'
            .' encryptedVerifierHashInput="'.base64_encode($encryptedVerifierInput).'"'
            .' encryptedVerifierHashValue="'.base64_encode($encryptedVerifierValue).'"'
            .' encryptedKeyValue="'.base64_encode($encryptedKeyValue).'"/>'
            .'</keyEncryptor></keyEncryptors></encryption>';
    }
}
