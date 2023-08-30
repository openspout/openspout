<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Options;

final class PageSetup
{
    public const LANDSCAPE = 'landscape';
    public const PORTRAIT = 'PORTRAIT';

    private string $orientation;
    private int $paperSize;

    public function setOrientation(EnumPageOrientation $orientation): void
    {
        $this->orientation = $orientation->value;
    }

    public function setPaperSize(EnumPaperSize $size): void
    {
        $this->paperSize = $size->value;
    }

    public function getOrientation(): ?string
    {
        return $this->orientation;
    }

    public function getPaperSize(): ?int
    {
        return $this->paperSize;
    }
}
