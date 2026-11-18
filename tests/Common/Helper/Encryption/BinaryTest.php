<?php

declare(strict_types=1);

namespace OpenSpout\Common\Helper\Encryption;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class BinaryTest extends TestCase
{
    public function testShouldReadLittleEndianIntegers(): void
    {
        $data = "\xFF\x01\x02\x03\x04\x05\x06\x07\x08";

        self::assertSame(0x0201, Binary::uint16($data, 1));
        self::assertSame(0x04030201, Binary::uint32($data, 1));
        self::assertSame(0x0807060504030201, Binary::uint64($data, 1));
        self::assertSame(0xFFFFFFFE, Binary::uint32("\xFE\xFF\xFF\xFF", 0));
    }

    public function testFitShouldTruncateOrPad(): void
    {
        self::assertSame('abc', Binary::fit('abcdef', 3));
        self::assertSame('abcdef', Binary::fit('abcdef', 6));
        self::assertSame("abcdef\x36\x36", Binary::fit('abcdef', 8));
    }

    #[DataProvider('provideUtf8ToUtf16leCases')]
    public function testUtf8ToUtf16le(string $utf8, string $expectedHex): void
    {
        self::assertSame($expectedHex, bin2hex(Binary::utf8ToUtf16le($utf8)));
    }

    public static function provideUtf8ToUtf16leCases(): iterable
    {
        return [
            'empty' => ['', ''],
            'ascii' => ['aZ~', '61005a007e00'],
            '2 bytes' => ["\u{80}é\u{7FF}", '8000e900ff07'],
            '3 bytes' => ["\u{800}€\u{FFFF}", '0008ac20ffff'],
            '4 bytes' => ["\u{10000}😀\u{10FFFF}", '00d800dc3dd800deffdbffdf'],
            'mixed' => ['pä€😀!', '7000e400ac203dd800de2100'],
        ];
    }

    public function testUtf8ToUtf16leShouldRejectInvalidUtf8(): void
    {
        self::assertNull(Binary::utf8ToUtf16le("\xFF\xFE"));
    }

    public function testHashPassword(): void
    {
        self::assertSame(sha1('saltpassword'), bin2hex(Binary::hashPassword('sha1', 'salt', 'password', 0)));

        $expected = sha1("\x01\x00\x00\x00".sha1("\x00\x00\x00\x00".sha1('saltpassword', true), true));
        self::assertSame($expected, bin2hex(Binary::hashPassword('sha1', 'salt', 'password', 2)));
    }
}
