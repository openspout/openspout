<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Encryption;

use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Helper\Encryption\Binary;
use SensitiveParameter;

/**
 * Turns a package (the ZIP file of an Office document) into a password-protected document:
 * this is a compound file holding the encrypted version of the package.
 *
 * @see https://learn.microsoft.com/openspecs/office_file_formats/ms-offcrypto
 *
 * @internal
 */
final readonly class PackageEncryptor
{
    /**
     * What a password has to match: Office does not support passwords of more than 255 characters.
     */
    public const PASSWORD_PATTERN = '/\A.{1,255}\z/su';

    /**
     * @param string   $password UTF-8 encoded password
     * @param resource $stream   Where the password-protected document gets written
     *
     * @throws IOException If the package is too big
     */
    public function encrypt(string $packageFilePath, #[SensitiveParameter] string $password, $stream): void
    {
        $packageSize = filesize($packageFilePath);
        $package = fopen($packageFilePath, 'r');
        $encodedPassword = Binary::utf8ToUtf16le($password);
        \assert(false !== $packageSize && false !== $package && null !== $encodedPassword);

        $writer = new CompoundFileWriter($stream, AgileEncryptor::getEncryptedPackageSize($packageSize), AgileEncryptor::getEncryptionInfoSize());
        $encryptor = new AgileEncryptor($encodedPassword);

        $writer->writePackage($encryptor->start($packageSize));
        for ($segment = 0, $remainingSize = $packageSize; $remainingSize > 0; ++$segment, $remainingSize -= AgileEncryptor::SEGMENT_SIZE) {
            $data = fread($package, min(AgileEncryptor::SEGMENT_SIZE, $remainingSize));
            \assert(false !== $data);
            $writer->writePackage($encryptor->encryptSegment($segment, $data));
        }
        $writer->finish($encryptor->getEncryptionInfo());
    }
}
