<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX\Encryption;

use OpenSpout\Reader\Exception\EncryptedFileCorruptedException;

/**
 * @internal
 */
interface CipherInterface
{
    /**
     * Derives the decryption key from the given password.
     * It must be called, and must succeed, before any other method.
     *
     * @param string $password UTF-16LE encoded password
     *
     * @return bool Whether the password is the right one
     *
     * @throws EncryptedFileCorruptedException
     */
    public function unlock(string $password): bool;

    /**
     * Decrypts one segment of the encrypted package.
     *
     * @param int    $index Zero based index of the segment
     * @param string $data  Encrypted segment, whose length is a multiple of the AES block size
     *
     * @throws EncryptedFileCorruptedException
     */
    public function decryptSegment(int $index, string $data): string;

    /**
     * Feeds the integrity check with the next raw bytes of the encrypted package stream.
     */
    public function updateIntegrity(string $data): void;

    /**
     * Returns whether the bytes given to updateIntegrity() are the ones that were originally encrypted.
     */
    public function isIntact(): bool;
}
