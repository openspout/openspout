<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Encryption;

use OpenSpout\Common\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class PasswordTest extends TestCase
{
    public function testGenerateShouldGiveBase64EncodedRandomBytes(): void
    {
        $password = Password::generate();

        self::assertMatchesRegularExpression('#\A[A-Za-z0-9+/]{43}=\z#', $password);
        $bytes = base64_decode($password, true);
        self::assertIsString($bytes);
        self::assertSame(32, \strlen($bytes));
    }

    public function testGenerateShouldNeverGiveSamePassword(): void
    {
        $passwords = [];
        for ($i = 0; $i < 100; ++$i) {
            $passwords[] = Password::generate();
        }

        self::assertCount(100, array_unique($passwords));
    }

    public function testGeneratedPasswordShouldBeValid(): void
    {
        Password::validate(Password::generate());

        $this->expectNotToPerformAssertions();
    }

    #[DataProvider('provideValidPasswordCases')]
    public function testValidPassword(string $password): void
    {
        Password::validate($password);

        $this->expectNotToPerformAssertions();
    }

    public static function provideValidPasswordCases(): iterable
    {
        return [
            'shortest' => [str_repeat('a', 12)],
            'shortest, with multibyte characters' => [str_repeat('😀', 12)],
            'longest' => [str_repeat('é', 255)],
            'with line feeds' => [str_repeat("\n", 12)],
        ];
    }

    #[DataProvider('provideInvalidPasswordCases')]
    public function testInvalidPassword(string $password): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The password must be a UTF-8 string of 12 to 255 characters.');

        Password::validate($password);
    }

    public static function provideInvalidPasswordCases(): iterable
    {
        return [
            'empty' => [''],
            'too short' => [str_repeat('a', 11)],
            'too short, with multibyte characters' => [str_repeat('😀', 11)],
            'too long' => [str_repeat('é', 256)],
            'too long, with a valid beginning' => [str_repeat('a', 255)."\n"],
            'too long, with a valid end' => ["\n".str_repeat('a', 255)],
            'not UTF-8' => ["secret passw\xE9rd"],
        ];
    }
}
