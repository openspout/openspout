<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Cell;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Comment\Comment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Common\Exception\UnsupportedImageTypeException;

final readonly class ImageCell extends Cell
{
    public string $mimeType;

    public function __construct(
        private string $path,
        public int $width,
        public int $height,
        ?Style $style = null,
        ?Comment $comment = null,
        public bool $fitToCell = false,
    ) {
        if (!file_exists($path)) {
            throw new InvalidArgumentException("Image file not found: {$path}");
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $this->mimeType = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'webp' => 'image/webp',
            default => throw new UnsupportedImageTypeException("Unsupported image type: .{$ext}"),
        };

        parent::__construct($style, $comment);
    }

    public function getValue(): string
    {
        return $this->path;
    }

    public function getExtension(): string
    {
        return match ($this->mimeType) {
            'image/jpeg' => 'jpeg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/bmp' => 'bmp',
            'image/webp' => 'webp',
        };
    }

    public function withFitToCell(bool $fitToCell): self
    {
        return new self($this->path, $this->width, $this->height, $this->style, $this->comment, $fitToCell);
    }

    public function withStyle(Style $style): static
    {
        return new self($this->path, $this->width, $this->height, $style, $this->comment, $this->fitToCell);
    }

    public function withoutStyle(): static
    {
        return new self($this->path, $this->width, $this->height, null, $this->comment, $this->fitToCell);
    }

    public function withComment(Comment $comment): static
    {
        return new self($this->path, $this->width, $this->height, $this->style, $comment, $this->fitToCell);
    }

    public function withoutComment(): static
    {
        return new self($this->path, $this->width, $this->height, $this->style, null, $this->fitToCell);
    }
}
