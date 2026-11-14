<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX\Encryption;

use DOMDocument;
use DOMElement;
use HashContext;
use OpenSpout\Common\Helper\Encryption\Binary;
use OpenSpout\Reader\Exception\EncryptedFileCorruptedException;
use OpenSpout\Reader\Exception\UnsupportedEncryptionException;

/**
 * "Agile" encryption, as written by Excel 2010 and later: AES in CBC mode, where the package
 * is encrypted by segments of 4096 bytes and protected by an HMAC.
 *
 * @see https://learn.microsoft.com/openspecs/office_file_formats/ms-offcrypto (2.3.4.10 to 2.3.4.15)
 *
 * @internal
 */
final class AgileCipher implements CipherInterface
{
    /**
     * Hashing a password more than this is considered to be a denial of service.
     */
    public const MAX_SPIN_COUNT = 10_000_000;

    private const NAMESPACE_ENCRYPTION = 'http://schemas.microsoft.com/office/2006/encryption';
    private const NAMESPACE_PASSWORD = 'http://schemas.microsoft.com/office/2006/keyEncryptor/password';

    private const BLOCK_SIZE = 16;
    private const BLOCK_KEY_VERIFIER_INPUT = "\xFE\xA7\xD2\x76\x3B\x4B\x9E\x79";
    private const BLOCK_KEY_VERIFIER_VALUE = "\xD7\xAA\x0F\x6D\x30\x61\x34\x4E";
    private const BLOCK_KEY_KEY_VALUE = "\x14\x6E\x0B\xE7\xAB\xAC\xD0\xD6";
    private const BLOCK_KEY_HMAC_KEY = "\x5F\xB2\xAD\x01\x0C\xB9\xE1\xF6";
    private const BLOCK_KEY_HMAC_VALUE = "\xA0\x67\x7F\x02\xB2\x2C\x84\x33";

    private const HASH_ALGORITHMS = [
        'SHA1' => 'sha1',
        'SHA-1' => 'sha1',
        'SHA256' => 'sha256',
        'SHA384' => 'sha384',
        'SHA512' => 'sha512',
    ];

    private const KEY_BITS = [128, 192, 256];

    /** @var array{keyBits: int, hashAlgorithm: string, salt: string} How the package is encrypted */
    private readonly array $package;

    /** @var array{keyBits: int, hashAlgorithm: string, salt: string} How the key of the package is encrypted */
    private readonly array $password;

    private readonly int $spinCount;
    private readonly string $encryptedVerifierInput;
    private readonly string $encryptedVerifierValue;
    private readonly string $encryptedKeyValue;
    private readonly string $encryptedHmacKey;
    private readonly string $encryptedHmacValue;

    private string $key = '';
    private string $hmacValue = '';
    private HashContext $hmac;

    /**
     * @param string $encryptionInfo Content of the "EncryptionInfo" stream
     *
     * @throws EncryptedFileCorruptedException
     * @throws UnsupportedEncryptionException
     */
    public function __construct(string $encryptionInfo)
    {
        // Version (4 bytes) and flags (4 bytes) are followed by an XML document
        $xml = substr($encryptionInfo, 8);
        $document = new DOMDocument();
        if ('' === $xml || !@$document->loadXML($xml, LIBXML_NONET) || null !== $document->doctype) {
            throw new EncryptedFileCorruptedException('The encryption information is malformed.');
        }

        $keyData = self::element($document, self::NAMESPACE_ENCRYPTION, 'keyData');
        $dataIntegrity = self::element($document, self::NAMESPACE_ENCRYPTION, 'dataIntegrity');
        $encryptedKey = $document->getElementsByTagNameNS(self::NAMESPACE_PASSWORD, 'encryptedKey')->item(0);
        if (null === $encryptedKey) {
            throw new UnsupportedEncryptionException('Only files encrypted with a password are supported.');
        }

        $this->package = self::algorithms($keyData);
        $this->password = self::algorithms($encryptedKey);
        $this->spinCount = (int) $encryptedKey->getAttribute('spinCount');
        if ($this->spinCount < 0 || $this->spinCount > self::MAX_SPIN_COUNT) {
            throw new UnsupportedEncryptionException('The spin count of the encryption is out of range.');
        }

        $this->encryptedVerifierInput = self::binaryAttribute($encryptedKey, 'encryptedVerifierHashInput');
        $this->encryptedVerifierValue = self::binaryAttribute($encryptedKey, 'encryptedVerifierHashValue');
        $this->encryptedKeyValue = self::binaryAttribute($encryptedKey, 'encryptedKeyValue');
        $this->encryptedHmacKey = self::binaryAttribute($dataIntegrity, 'encryptedHmacKey');
        $this->encryptedHmacValue = self::binaryAttribute($dataIntegrity, 'encryptedHmacValue');
    }

    public function unlock(string $password): bool
    {
        ['keyBits' => $keyBits, 'hashAlgorithm' => $hashAlgorithm, 'salt' => $salt] = $this->password;
        $passwordHash = Binary::hashPassword($hashAlgorithm, $salt, $password, $this->spinCount);
        $decrypt = static function (string $blockKey, string $data) use ($keyBits, $hashAlgorithm, $salt, $passwordHash): string {
            $key = Binary::fit(hash($hashAlgorithm, $passwordHash.$blockKey, true), intdiv($keyBits, 8));

            return Aes::decrypt('cbc', $keyBits, $key, $data, Binary::fit($salt, self::BLOCK_SIZE));
        };

        // The verifier is a random value, as long as the salt, stored next to its hash
        $verifier = substr($decrypt(self::BLOCK_KEY_VERIFIER_INPUT, $this->encryptedVerifierInput), 0, \strlen($salt));
        $verifierHash = hash($hashAlgorithm, $verifier, true);
        $expectedVerifierHash = $decrypt(self::BLOCK_KEY_VERIFIER_VALUE, $this->encryptedVerifierValue);
        if (!hash_equals($verifierHash, substr($expectedVerifierHash, 0, \strlen($verifierHash)))) {
            return false;
        }

        $this->key = Binary::fit($decrypt(self::BLOCK_KEY_KEY_VALUE, $this->encryptedKeyValue), intdiv($this->package['keyBits'], 8));

        $hashSize = \strlen(hash($this->package['hashAlgorithm'], '', true));
        $hmacKey = substr($this->decryptWithPackageKey(self::BLOCK_KEY_HMAC_KEY, $this->encryptedHmacKey), 0, $hashSize);
        $this->hmacValue = substr($this->decryptWithPackageKey(self::BLOCK_KEY_HMAC_VALUE, $this->encryptedHmacValue), 0, $hashSize);
        $this->hmac = hash_init($this->package['hashAlgorithm'], HASH_HMAC, $hmacKey);

        return true;
    }

    public function decryptSegment(int $index, string $data): string
    {
        return $this->decryptWithPackageKey(pack('V', $index), $data);
    }

    public function updateIntegrity(string $data): void
    {
        hash_update($this->hmac, $data);
    }

    public function isIntact(): bool
    {
        return hash_equals($this->hmacValue, hash_final($this->hmac, true));
    }

    /**
     * Everything encrypted with the key of the package uses an initialization vector derived from a "block key".
     */
    private function decryptWithPackageKey(string $blockKey, string $data): string
    {
        $iv = Binary::fit(hash($this->package['hashAlgorithm'], $this->package['salt'].$blockKey, true), self::BLOCK_SIZE);

        return Aes::decrypt('cbc', $this->package['keyBits'], $this->key, $data, $iv);
    }

    private static function element(DOMDocument $document, string $namespace, string $name): DOMElement
    {
        return $document->getElementsByTagNameNS($namespace, $name)->item(0)
            ?? throw new EncryptedFileCorruptedException("The encryption information has no \"{$name}\" element.");
    }

    /**
     * @return array{keyBits: int, hashAlgorithm: string, salt: string}
     */
    private static function algorithms(DOMElement $element): array
    {
        $keyBits = (int) $element->getAttribute('keyBits');
        $hashAlgorithm = self::HASH_ALGORITHMS[$element->getAttribute('hashAlgorithm')] ?? null;
        if ('AES' !== $element->getAttribute('cipherAlgorithm')
            || 'ChainingModeCBC' !== $element->getAttribute('cipherChaining')
            || !\in_array($keyBits, self::KEY_BITS, true)
            || null === $hashAlgorithm
        ) {
            throw new UnsupportedEncryptionException('Only AES in CBC mode, with a SHA-1 or SHA-2 hash, is supported for files using the agile encryption.');
        }

        return ['keyBits' => $keyBits, 'hashAlgorithm' => $hashAlgorithm, 'salt' => self::binaryAttribute($element, 'saltValue')];
    }

    private static function binaryAttribute(DOMElement $element, string $name): string
    {
        $value = base64_decode($element->getAttribute($name), true);
        if (false === $value || '' === $value) {
            throw new EncryptedFileCorruptedException("The encryption information has no valid \"{$name}\" attribute.");
        }

        return $value;
    }
}
