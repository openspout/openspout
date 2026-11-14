<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX\Encryption;

use OpenSpout\Reader\Exception\EncryptedFileCorruptedException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class AesTest extends TestCase
{
    public function testDecrypt(): void
    {
        $key = str_repeat('k', 16);
        $iv = str_repeat('i', 16);
        $plain = str_repeat('0123456789abcdef', 2);

        $ecb = openssl_encrypt($plain, 'aes-128-ecb', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
        $cbc = openssl_encrypt($plain, 'aes-128-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
        self::assertIsString($ecb);
        self::assertIsString($cbc);
        self::assertNotSame($ecb, $cbc);

        self::assertSame($plain, Aes::decrypt('ecb', 128, $key, $ecb));
        self::assertSame($plain, Aes::decrypt('cbc', 128, $key, $cbc, $iv));
    }

    public function testDecryptShouldRejectPartialBlocks(): void
    {
        $this->expectException(EncryptedFileCorruptedException::class);

        Aes::decrypt('ecb', 128, str_repeat('k', 16), str_repeat('x', 17));
    }
}
