<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Cell;

use Imagick;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Comment\Comment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Common\Exception\UnsupportedImageTypeException;
use OpenSpout\Common\Exception\UnsupportedTypeException;

final readonly class ImageCell extends Cell
{
    public int $width;
    public int $height;
    public string $mimeType;

    public function __construct(
        private string $path,
        ?Style $style = null,
        ?Comment $comment = null,
        public bool $fitToCell = false,
    ) {
        if (!\extension_loaded('imagick') && !\extension_loaded('gd')) {
            throw new UnsupportedTypeException('Neither the imagick nor gd PHP extension is available.');
        }

        if (!file_exists($path)) {
            throw new InvalidArgumentException("Image file not found: {$path}");
        }

        [$this->width, $this->height, $this->mimeType] = self::getImageInfo($path);

        parent::__construct($style, $comment);
    }

    public function getValue(): string
    {
        return $this->path;
    }

    /**
     * @throws UnsupportedImageTypeException
     */
    public function getExtension(): string
    {
        return match ($this->mimeType) {
            'image/jpeg' => 'jpeg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/bmp' => 'bmp',
            'image/webp' => 'webp',
            default => throw new UnsupportedImageTypeException("Unsupported image MIME type: {$this->mimeType}"),
        };
    }

    public function withFitToCell(bool $fitToCell): self
    {
        return new self($this->path, $this->style, $this->comment, $fitToCell);
    }

    public function withStyle(Style $style): static
    {
        return new self($this->path, $style, $this->comment, $this->fitToCell);
    }

    public function withoutStyle(): static
    {
        return new self($this->path, null, $this->comment, $this->fitToCell);
    }

    public function withComment(Comment $comment): static
    {
        return new self($this->path, $this->style, $comment, $this->fitToCell);
    }

    public function withoutComment(): static
    {
        return new self($this->path, $this->style, null, $this->fitToCell);
    }

    /**
     * @return array{int, int, string} [width, height, mimeType]
     *
     * @throws UnsupportedImageTypeException
     */
    private static function getImageInfo(string $path): array
    {
        if (\extension_loaded('imagick')) {
            $imagick = new Imagick($path);
            $width = $imagick->getImageWidth();
            $height = $imagick->getImageHeight();
            $format = strtolower($imagick->getImageFormat());
            $imagick->clear();
            $mimeType = match ($format) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'bmp' => 'image/bmp',
                'webp' => 'image/webp',
                default => throw new UnsupportedImageTypeException("Unsupported image format: {$format}")
            };

            return [$width, $height, $mimeType];
        }

        $info = getimagesize($path);
        if (false === $info) {
            throw new InvalidArgumentException("Could not read image info for: {$path}");
        }

        return [$info[0], $info[1], $info['mime']];
    }
}
