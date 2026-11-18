<?php

declare(strict_types=1);

namespace OpenSpout\Common\Helper\Encryption;

/**
 * Low level helpers to read and write encrypted Office documents, where integers are little-endian.
 *
 * @internal
 */
final class Binary
{
    public static function uint16(string $data, int $offset): int
    {
        return self::unpack('v', $data, $offset);
    }

    public static function uint32(string $data, int $offset): int
    {
        return self::unpack('V', $data, $offset);
    }

    public static function uint64(string $data, int $offset): int
    {
        return self::unpack('P', $data, $offset);
    }

    /**
     * Truncates or pads the given value so that it is exactly $length bytes long.
     * The padding byte is the one mandated by MS-OFFCRYPTO.
     */
    public static function fit(string $value, int $length): string
    {
        return str_pad(substr($value, 0, $length), $length, "\x36");
    }

    /**
     * Converts a UTF-8 string to UTF-16LE, without relying on any optional extension.
     *
     * @return null|string The converted string, or null if the given string is not valid UTF-8
     */
    public static function utf8ToUtf16le(string $utf8): ?string
    {
        $chars = preg_split('//u', $utf8, -1, PREG_SPLIT_NO_EMPTY);
        if (false === $chars) {
            return null;
        }

        $utf16 = '';
        foreach ($chars as $char) {
            $bytes = array_map(\ord(...), str_split($char));
            $codePoint = match (\count($bytes)) {
                1 => $bytes[0],
                2 => ($bytes[0] & 0x1F) << 6 | ($bytes[1] & 0x3F),
                3 => ($bytes[0] & 0x0F) << 12 | ($bytes[1] & 0x3F) << 6 | ($bytes[2] & 0x3F),
                default => ($bytes[0] & 0x07) << 18 | ($bytes[1] & 0x3F) << 12 | ($bytes[2] & 0x3F) << 6 | ($bytes[3] & 0x3F),
            };

            if ($codePoint > 0xFFFF) {
                $codePoint -= 0x10000;
                $utf16 .= pack('vv', 0xD800 | ($codePoint >> 10), 0xDC00 | ($codePoint & 0x3FF));
            } else {
                $utf16 .= pack('v', $codePoint);
            }
        }

        return $utf16;
    }

    /**
     * Hashes the password the way MS-OFFCRYPTO requires it:
     * H(0) = H(salt + password), H(n) = H(n-1 as uint32 + H(n-1)).
     *
     * @param string $password UTF-16LE encoded password
     */
    public static function hashPassword(string $algorithm, string $salt, string $password, int $spinCount): string
    {
        $hash = hash($algorithm, $salt.$password, true);
        for ($i = 0; $i < $spinCount; ++$i) {
            $hash = hash($algorithm, pack('V', $i).$hash, true);
        }

        return $hash;
    }

    private static function unpack(string $format, string $data, int $offset): int
    {
        $values = unpack($format, $data, $offset);
        \assert(false !== $values && \is_int($values[1]));

        return $values[1];
    }
}
