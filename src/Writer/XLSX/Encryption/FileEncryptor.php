<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Encryption;

use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Common\Exception\IOException;
use SensitiveParameter;

/**
 * Protects an existing file with a password: the result is the original file, encrypted the same way Excel does it.
 */
final readonly class FileEncryptor
{
    /**
     * @param string $filePath          Path of the XLSX file to protect
     * @param string $password          UTF-8 encoded password, of 255 characters at most
     * @param string $encryptedFilePath Path of the file to create, which gets overwritten if it exists: it can be the file to protect
     *
     * @throws InvalidArgumentException If the password is not valid
     * @throws IOException              If the file is not a readable XLSX file, if it is too big, or if the protected file cannot be created
     */
    public function encrypt(string $filePath, #[SensitiveParameter] string $password, string $encryptedFilePath): void
    {
        if (1 !== preg_match(PackageEncryptor::PASSWORD_PATTERN, $password)) {
            throw new InvalidArgumentException('The password must be a UTF-8 string of 1 to 255 characters.');
        }

        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new IOException("Could not open {$filePath} for reading.");
        }

        // XLSX files are ZIP files, unless they are already password-protected
        if ("PK\x03\x04" !== file_get_contents($filePath, length: 4)) {
            throw new IOException("{$filePath} is not an XLSX file that can be password-protected.");
        }

        // The file is encrypted next to its final location, so that it only appears there once complete
        $folder = \dirname($encryptedFilePath);
        if (!is_dir($folder) || !is_writable($folder)) {
            throw new IOException("Could not create a file in {$folder}.");
        }

        $temporaryFilePath = tempnam($folder, 'openspout_xlsx_');
        \assert(false !== $temporaryFilePath);
        $stream = fopen($temporaryFilePath, 'w');
        \assert(false !== $stream);

        try {
            (new PackageEncryptor())->encrypt($filePath, $password, $stream);
        } catch (IOException $exception) {
            fclose($stream);
            unlink($temporaryFilePath);

            throw $exception;
        }

        fclose($stream);
        rename($temporaryFilePath, $encryptedFilePath);
    }
}
