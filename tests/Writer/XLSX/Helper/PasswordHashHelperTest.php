<?php

declare(strict_types=1);

namespace Writer\XLSX\Helper;

use OpenSpout\Writer\XLSX\Helper\PasswordHashHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PasswordHashHelper::class)]
final class PasswordHashHelperTest extends TestCase
{
    public function testPasswordHashing(): void
    {
        self::assertEquals(PasswordHashHelper::make('password'), '83AF');
        self::assertEquals(PasswordHashHelper::make('longPasswordWhichIs35CharactersLong'), 'CCC8');
    }
}
