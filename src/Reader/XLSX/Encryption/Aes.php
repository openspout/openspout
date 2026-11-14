<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX\Encryption;

use OpenSpout\Reader\Exception\EncryptedFileCorruptedException;

/**
 * @internal
 */
final class Aes
{
    /**
     * Decrypts AES encrypted data that is not padded the PKCS way.
     *
     * @param 'cbc'|'ecb' $mode
     *
     * @throws EncryptedFileCorruptedException
     */
    public static function decrypt(string $mode, int $keyBits, string $key, string $data, string $iv = ''): string
    {
        $decrypted = openssl_decrypt($data, "aes-{$keyBits}-{$mode}", $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
        if (false === $decrypted) {
            throw new EncryptedFileCorruptedException('The encrypted data is not a whole number of AES blocks.');
        }

        return $decrypted;
    }
}
