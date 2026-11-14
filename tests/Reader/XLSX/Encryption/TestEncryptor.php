<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX\Encryption;

/**
 * Encrypts packages the way Office does, to test the decryption with content that is under control.
 * It purposely shares no code with the classes it helps testing.
 *
 * Whatever needs to be padded is padded with garbage, as nothing in the specification mandates zeros.
 *
 * @internal
 */
final class TestEncryptor
{
    private const PADDING = "\xA5";

    /**
     * @param array{keyBits?: int, hashAlgorithm?: string, saltSize?: int}                  $keyData      How the package is encrypted
     * @param array{keyBits?: int, hashAlgorithm?: string, saltSize?: int, spinCount?: int} $encryptedKey How the key of the package is encrypted
     * @param string                                                                        $trailingData Data that follows the encrypted data, and that is covered by the integrity check
     *
     * @return array{string, string} Content of the "EncryptionInfo" and of the "EncryptedPackage" streams
     */
    public static function agile(string $package, string $password, array $keyData = [], array $encryptedKey = [], string $trailingData = ''): array
    {
        $keyData += ['keyBits' => 256, 'hashAlgorithm' => 'SHA512', 'saltSize' => 16];
        $encryptedKey += ['keyBits' => 256, 'hashAlgorithm' => 'SHA512', 'saltSize' => 16, 'spinCount' => 100];
        $packageHash = strtolower($keyData['hashAlgorithm']);
        $passwordHash = strtolower($encryptedKey['hashAlgorithm']);
        $packageCipher = "aes-{$keyData['keyBits']}-cbc";
        $passwordCipher = "aes-{$encryptedKey['keyBits']}-cbc";

        $packageSalt = self::bytes('package salt', $keyData['saltSize']);
        $passwordSalt = self::bytes('password salt', $encryptedKey['saltSize']);
        $packageKey = self::bytes('package key', intdiv($keyData['keyBits'], 8));
        $verifier = self::bytes('verifier', $encryptedKey['saltSize']);
        $hmacKey = self::bytes('hmac key', \strlen(hash($packageHash, '', true)));

        $hash = hash($passwordHash, $passwordSalt.self::utf16le($password), true);
        for ($i = 0; $i < $encryptedKey['spinCount']; ++$i) {
            $hash = hash($passwordHash, pack('V', $i).$hash, true);
        }
        $encryptWithPassword = static function (string $blockKey, string $data) use ($passwordCipher, $passwordHash, $passwordSalt, $hash, $encryptedKey): string {
            $key = self::fit(hash($passwordHash, $hash.$blockKey, true), intdiv($encryptedKey['keyBits'], 8));

            return self::encrypt($passwordCipher, $key, $data, self::fit($passwordSalt, 16));
        };
        $encryptWithPackageKey = static function (string $blockKey, string $data) use ($packageCipher, $packageHash, $packageSalt, $packageKey): string {
            return self::encrypt($packageCipher, $packageKey, $data, self::fit(hash($packageHash, $packageSalt.$blockKey, true), 16));
        };

        $encryptedPackage = pack('P', \strlen($package));
        foreach ('' === $package ? [] : str_split($package, 4096) as $index => $segment) {
            $encryptedPackage .= $encryptWithPackageKey(pack('V', $index), $segment);
        }
        $encryptedPackage .= $trailingData;

        $attributes = static function (array $values): string {
            $xml = '';
            foreach ($values as $name => $value) {
                $xml .= " {$name}=\"{$value}\"";
            }

            return $xml;
        };

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<encryption xmlns="http://schemas.microsoft.com/office/2006/encryption" xmlns:p="http://schemas.microsoft.com/office/2006/keyEncryptor/password">'
            .'<keyData'.$attributes([
                'saltSize' => $keyData['saltSize'],
                'blockSize' => 16,
                'keyBits' => $keyData['keyBits'],
                'hashSize' => \strlen($hmacKey),
                'cipherAlgorithm' => 'AES',
                'cipherChaining' => 'ChainingModeCBC',
                'hashAlgorithm' => $keyData['hashAlgorithm'],
                'saltValue' => base64_encode($packageSalt),
            ]).'/>'
            .'<dataIntegrity'.$attributes([
                'encryptedHmacKey' => base64_encode($encryptWithPackageKey("\x5F\xB2\xAD\x01\x0C\xB9\xE1\xF6", $hmacKey)),
                'encryptedHmacValue' => base64_encode($encryptWithPackageKey("\xA0\x67\x7F\x02\xB2\x2C\x84\x33", hash_hmac($packageHash, $encryptedPackage, $hmacKey, true))),
            ]).'/>'
            .'<keyEncryptors><keyEncryptor uri="http://schemas.microsoft.com/office/2006/keyEncryptor/password">'
            .'<p:encryptedKey'.$attributes([
                'spinCount' => $encryptedKey['spinCount'],
                'saltSize' => $encryptedKey['saltSize'],
                'blockSize' => 16,
                'keyBits' => $encryptedKey['keyBits'],
                'hashSize' => \strlen($hash),
                'cipherAlgorithm' => 'AES',
                'cipherChaining' => 'ChainingModeCBC',
                'hashAlgorithm' => $encryptedKey['hashAlgorithm'],
                'saltValue' => base64_encode($passwordSalt),
                'encryptedVerifierHashInput' => base64_encode($encryptWithPassword("\xFE\xA7\xD2\x76\x3B\x4B\x9E\x79", $verifier)),
                'encryptedVerifierHashValue' => base64_encode($encryptWithPassword("\xD7\xAA\x0F\x6D\x30\x61\x34\x4E", hash($passwordHash, $verifier, true))),
                'encryptedKeyValue' => base64_encode($encryptWithPassword("\x14\x6E\x0B\xE7\xAB\xAC\xD0\xD6", $packageKey)),
            ]).'/>'
            .'</keyEncryptor></keyEncryptors></encryption>';

        return [pack('vvV', 4, 4, 0x40).$xml, $encryptedPackage];
    }

    /**
     * @param int $algorithm 0x660E for AES-128, 0x660F for AES-192, 0x6610 for AES-256
     *
     * @return array{string, string} Content of the "EncryptionInfo" and of the "EncryptedPackage" streams
     */
    public static function standard(string $package, string $password, int $algorithm = 0x660E, int $versionMajor = 3): array
    {
        $keyBits = [0x660E => 128, 0x660F => 192, 0x6610 => 256][$algorithm] ?? 128;
        $cipher = "aes-{$keyBits}-ecb";
        $salt = self::bytes('salt', 16);
        $verifier = self::bytes('verifier', 16);

        $hash = sha1($salt.self::utf16le($password), true);
        for ($i = 0; $i < 50000; ++$i) {
            $hash = sha1(pack('V', $i).$hash, true);
        }
        $hash = sha1($hash.pack('V', 0), true);
        $inner = '';
        $outer = '';
        foreach (str_split(str_pad($hash, 64, "\0")) as $byte) {
            $inner .= \chr(\ord($byte) ^ 0x36);
            $outer .= \chr(\ord($byte) ^ 0x5C);
        }
        $key = substr(sha1($inner, true).sha1($outer, true), 0, intdiv($keyBits, 8));

        $header = pack('V8', 0x24, 0, $algorithm, 0x8004, $keyBits, 0x18, 0, 0)
            .self::utf16le("Microsoft Enhanced RSA and AES Cryptographic Provider\0");
        $encryptionInfo = pack('vvVV', $versionMajor, 2, 0x24, \strlen($header)).$header
            .pack('V', 16).$salt.self::encrypt($cipher, $key, $verifier)
            .pack('V', 20).self::encrypt($cipher, $key, sha1($verifier, true));

        return [$encryptionInfo, pack('P', \strlen($package)).self::encrypt($cipher, $key, $package)];
    }

    private static function encrypt(string $cipher, string $key, string $data, string $iv = ''): string
    {
        $padded = str_pad($data, 16 * intdiv(\strlen($data) + 15, 16), self::PADDING);
        $encrypted = openssl_encrypt($padded, $cipher, $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
        \assert(false !== $encrypted);

        return $encrypted;
    }

    private static function fit(string $value, int $length): string
    {
        return \strlen($value) > $length ? substr($value, 0, $length) : $value.str_repeat("\x36", $length - \strlen($value));
    }

    private static function bytes(string $seed, int $length): string
    {
        $bytes = '';
        for ($i = 0; \strlen($bytes) < $length; ++$i) {
            $bytes .= hash('sha256', $seed.$i, true);
        }

        return substr($bytes, 0, $length);
    }

    private static function utf16le(string $utf8): string
    {
        return mb_convert_encoding($utf8, 'UTF-16LE', 'UTF-8');
    }
}
