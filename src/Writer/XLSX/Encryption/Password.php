<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Encryption;

use OpenSpout\Common\Exception\InvalidArgumentException;
use SensitiveParameter;

/**
 * Rules for the passwords of the password-protected files that get created, and a generator of strong passwords.
 */
final class Password
{
    /**
     * Shorter passwords are too easy to guess.
     */
    public const MIN_LENGTH = 12;

    /**
     * Office does not support passwords of more than 255 characters.
     */
    public const MAX_LENGTH = 255;

    /**
     * @return non-empty-string A random password, made of 44 characters of the base64 alphabet, holding 256 bits of entropy
     */
    public static function generate(): string
    {
        return base64_encode(random_bytes(32));
    }

    /**
     * @param string $password UTF-8 encoded password
     *
     * @throws InvalidArgumentException If the password cannot be used to protect a file
     */
    public static function validate(#[SensitiveParameter] string $password): void
    {
        if (1 !== preg_match('/\A.{'.self::MIN_LENGTH.','.self::MAX_LENGTH.'}\z/su', $password)) {
            throw new InvalidArgumentException('The password must be a UTF-8 string of '.self::MIN_LENGTH.' to '.self::MAX_LENGTH.' characters.');
        }
    }
}
