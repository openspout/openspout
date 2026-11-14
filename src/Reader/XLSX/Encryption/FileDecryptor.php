<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX\Encryption;

use OpenSpout\Common\Exception\IOException;
use OpenSpout\Reader\Exception\EncryptedFileCorruptedException;
use OpenSpout\Reader\Exception\InvalidPasswordException;
use OpenSpout\Reader\Exception\PasswordRequiredException;
use OpenSpout\Reader\Exception\UnsupportedEncryptionException;
use SensitiveParameter;

/**
 * Removes the password protection of a file: unlike what reading a file then writing its rows gives,
 * the result is the original file, with everything it contains.
 */
final readonly class FileDecryptor
{
    /**
     * Tells whether a file is encrypted, which is what happens to files that are given a password to be opened.
     * Note that Excel also encrypts some files without asking for a password: these can be read without giving any.
     *
     * @throws IOException If the file is not readable
     */
    public function isPasswordProtected(string $filePath): bool
    {
        $this->assertReadable($filePath);

        try {
            // Password-protected files are compound files holding these two streams
            $compoundFile = new CompoundFile($filePath);

            return $compoundFile->hasStream(PackageDecryptor::STREAM_ENCRYPTION_INFO)
                && $compoundFile->hasStream(PackageDecryptor::STREAM_ENCRYPTED_PACKAGE);
        } catch (EncryptedFileCorruptedException) {
            return false;
        }
    }

    /**
     * @param string      $filePath          Path of the password-protected file
     * @param null|string $password          UTF-8 encoded password
     * @param string      $decryptedFilePath Path of the file to create, which gets overwritten if it exists
     *
     * @throws IOException                     If the file is not a readable password-protected file, or if the decrypted file cannot be created
     * @throws PasswordRequiredException       If the file needs a password, and none is given
     * @throws InvalidPasswordException        If the password is not the right one
     * @throws UnsupportedEncryptionException  If the file is protected in a way that is not supported
     * @throws EncryptedFileCorruptedException If the file is corrupted
     */
    public function decrypt(string $filePath, #[SensitiveParameter] ?string $password, string $decryptedFilePath): void
    {
        $this->assertReadable($filePath);

        // The file is decrypted next to its final location, so that it only appears there once complete
        $folder = \dirname($decryptedFilePath);
        if (!is_dir($folder) || !is_writable($folder)) {
            throw new IOException("Could not create a file in {$folder}.");
        }

        $temporaryFilePath = CompoundFile::isCompoundFile($filePath)
            ? (new PackageDecryptor())->decrypt($filePath, $password, $folder)
            : null;
        if (null === $temporaryFilePath) {
            throw new IOException("{$filePath} is not a password-protected file.");
        }

        if (!@rename($temporaryFilePath, $decryptedFilePath)) {
            unlink($temporaryFilePath);

            throw new IOException("Could not create {$decryptedFilePath}.");
        }
    }

    private function assertReadable(string $filePath): void
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new IOException("Could not open {$filePath} for reading.");
        }
    }
}
