<?php

declare(strict_types=1);

namespace OpenSpout\Reader\XLSX\Encryption;

use OpenSpout\Common\Helper\Encryption\Binary;
use OpenSpout\Reader\Exception\EncryptedFileCorruptedException;
use OpenSpout\Reader\Exception\InvalidPasswordException;
use OpenSpout\Reader\Exception\PasswordRequiredException;
use OpenSpout\Reader\Exception\ReaderException;
use OpenSpout\Reader\Exception\UnsupportedEncryptionException;
use SensitiveParameter;
use Throwable;

/**
 * Decrypts password-protected Office documents: these are compound files
 * holding the encrypted version of the regular, ZIP based, document.
 *
 * @see https://learn.microsoft.com/openspecs/office_file_formats/ms-offcrypto
 *
 * @internal
 */
final readonly class PackageDecryptor
{
    /**
     * Password used by Excel when a file has to be encrypted but the user did not set any password.
     */
    public const DEFAULT_PASSWORD = 'VelvetSweatshop';

    public const STREAM_ENCRYPTION_INFO = 'EncryptionInfo';
    public const STREAM_ENCRYPTED_PACKAGE = 'EncryptedPackage';

    private const MAX_ENCRYPTION_INFO_SIZE = 65536;
    private const SEGMENT_SIZE = 4096;
    private const BLOCK_SIZE = 16;

    /**
     * Decrypts the given file into a temporary file, that the caller is responsible for deleting.
     *
     * @param string      $filePath Path of a compound file
     * @param null|string $password UTF-8 encoded password
     *
     * @return null|string Path of the decrypted file, or null if the given file is not an encrypted document
     *
     * @throws ReaderException If the file cannot be decrypted
     */
    public function decrypt(string $filePath, #[SensitiveParameter] ?string $password, string $tempFolder): ?string
    {
        $compoundFile = new CompoundFile($filePath);
        if (!$compoundFile->hasStream(self::STREAM_ENCRYPTION_INFO) || !$compoundFile->hasStream(self::STREAM_ENCRYPTED_PACKAGE)) {
            return null;
        }

        $cipher = $this->createCipher($compoundFile->readStream(self::STREAM_ENCRYPTION_INFO, self::MAX_ENCRYPTION_INFO_SIZE));
        $encodedPassword = Binary::utf8ToUtf16le($password ?? self::DEFAULT_PASSWORD)
            ?? throw new InvalidPasswordException('The password must be a valid UTF-8 string.');
        if (!$cipher->unlock($encodedPassword)) {
            throw null === $password
                ? new PasswordRequiredException('The file is encrypted: a password is required to read it.')
                : new InvalidPasswordException('The password is incorrect.');
        }

        $decryptedFilePath = tempnam($tempFolder, 'openspout_xlsx_');
        \assert(false !== $decryptedFilePath);

        try {
            $this->decryptPackage($compoundFile, $cipher, $decryptedFilePath);
        } catch (Throwable $throwable) {
            unlink($decryptedFilePath);

            throw $throwable;
        }

        return $decryptedFilePath;
    }

    private function createCipher(string $encryptionInfo): CipherInterface
    {
        // The stream starts with the version of the encryption: 2 bytes for the major one, 2 bytes for the minor one
        if (\strlen($encryptionInfo) < 4) {
            throw new EncryptedFileCorruptedException('The encryption information is malformed.');
        }

        $versionMajor = Binary::uint16($encryptionInfo, 0);
        $versionMinor = Binary::uint16($encryptionInfo, 2);

        return match (true) {
            4 === $versionMajor && 4 === $versionMinor => new AgileCipher($encryptionInfo),
            \in_array($versionMajor, [2, 3, 4], true) && 2 === $versionMinor => new StandardCipher($encryptionInfo),
            default => throw new UnsupportedEncryptionException("The encryption version {$versionMajor}.{$versionMinor} is not supported."),
        };
    }

    /**
     * The encrypted package starts with the size of the decrypted data,
     * followed by the data encrypted by segments that are padded to the AES block size.
     *
     * @throws EncryptedFileCorruptedException
     */
    private function decryptPackage(CompoundFile $compoundFile, CipherInterface $cipher, string $decryptedFilePath): void
    {
        $chunks = $compoundFile->streamChunks(self::STREAM_ENCRYPTED_PACKAGE);
        $buffer = '';
        $read = static function (int $length) use ($chunks, $cipher, &$buffer): string {
            // Chunks are only read until the buffer holds the requested bytes
            while (\strlen(substr($buffer, 0, $length)) !== $length && $chunks->valid()) {
                $buffer .= $chunks->current();
                $chunks->next();
            }
            $data = substr($buffer, 0, $length);
            $buffer = substr($buffer, $length);
            $cipher->updateIntegrity($data);

            return $data;
        };

        $header = $read(8);
        if (8 !== \strlen($header)) {
            throw new EncryptedFileCorruptedException('The encrypted package is truncated.');
        }

        $decryptedFile = fopen($decryptedFilePath, 'w');
        \assert(false !== $decryptedFile);

        $remainingSize = Binary::uint64($header, 0);
        for ($segment = 0; $remainingSize > 0; ++$segment) {
            $size = min(self::SEGMENT_SIZE, $remainingSize);
            $paddedSize = self::BLOCK_SIZE * intdiv($size + self::BLOCK_SIZE - 1, self::BLOCK_SIZE);
            $data = $read($paddedSize);
            if ($paddedSize !== \strlen($data)) {
                throw new EncryptedFileCorruptedException('The encrypted package is truncated.');
            }

            fwrite($decryptedFile, substr($cipher->decryptSegment($segment, $data), 0, $size));
            $remainingSize -= $size;
        }
        fclose($decryptedFile);

        // The integrity check covers the whole stream, even what follows the encrypted data
        while ('' !== $read(self::SEGMENT_SIZE));

        if (!$cipher->isIntact()) {
            throw new EncryptedFileCorruptedException('The encrypted package has been altered.');
        }
    }
}
