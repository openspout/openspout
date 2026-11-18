<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Manager;

use OpenSpout\Common\Entity\Cell\ImageCell;
use OpenSpout\Writer\Common\Entity\Worksheet;

/**
 * Tracks image paths across the whole workbook so that identical source files
 * are embedded in the XLSX archive exactly once, regardless of how many cells
 * reference them.
 *
 * The drawing anchor of each image cell is written to a temporary file per worksheet
 * as soon as the cell is added, so that memory usage does not grow with the number
 * of image cells. Only the distinct image paths are kept in memory.
 *
 * @internal
 */
final class ImageManager
{
    /** @var array<string, int> Maps absolute path to its global media ID */
    private array $pathToId = [];

    private int $nextId = 1;

    /** @var array<int, array<string, array{relId: int, extension: string}>> [sheetId => [path => drawing relationship]] */
    private array $sheetImages = [];

    /** @var array<int, int> [sheetId => number of image cells] */
    private array $imageCounts = [];

    /** @var array<string, string> [extension => mime type] of all images in the workbook */
    private array $mimeTypes = [];

    /** @var array<int, resource> [sheetId => drawing anchors file pointer] */
    private array $filePointers = [];

    public function __construct(
        private readonly string $tempFolder,
    ) {}

    /**
     * Returns true if this path has already been registered.
     * Call this *before* register() to decide whether the media file needs to
     * be copied.
     */
    public function has(string $path): bool
    {
        return isset($this->pathToId[$path]);
    }

    /**
     * Returns the global media ID for the given path, registering it with a
     * new ID if it has not been seen before.
     */
    public function register(string $path): int
    {
        if (!isset($this->pathToId[$path])) {
            $this->pathToId[$path] = $this->nextId++;
        }

        return $this->pathToId[$path];
    }

    /**
     * @param int $rowIndexZeroBased    Row of the cell containing the image
     * @param int $columnIndexZeroBased Column of the cell containing the image
     */
    public function addImage(Worksheet $worksheet, int $rowIndexZeroBased, int $columnIndexZeroBased, ImageCell $cell): void
    {
        $sheetId = $worksheet->getId();
        $path = $cell->getValue();

        // cells of the same sheet referencing the same file share one relationship
        if (!isset($this->sheetImages[$sheetId][$path])) {
            $this->sheetImages[$sheetId][$path] = [
                'relId' => \count($this->sheetImages[$sheetId] ?? []) + 1,
                'extension' => $cell->getExtension(),
            ];
            $this->mimeTypes[$cell->getExtension()] = $cell->mimeType;
        }

        $relId = $this->sheetImages[$sheetId][$path]['relId'];
        $imageCount = ($this->imageCounts[$sheetId] ?? 0) + 1;
        $this->imageCounts[$sheetId] = $imageCount;
        $picId = $imageCount + 1;

        if ($cell->fitToCell) {
            $anchorXml = '<xdr:twoCellAnchor editAs="twoCell">'
                .'<xdr:from>'
                .'<xdr:col>'.$columnIndexZeroBased.'</xdr:col><xdr:colOff>0</xdr:colOff>'
                .'<xdr:row>'.$rowIndexZeroBased.'</xdr:row><xdr:rowOff>0</xdr:rowOff>'
                .'</xdr:from>'
                .'<xdr:to>'
                .'<xdr:col>'.($columnIndexZeroBased + 1).'</xdr:col><xdr:colOff>0</xdr:colOff>'
                .'<xdr:row>'.($rowIndexZeroBased + 1).'</xdr:row><xdr:rowOff>0</xdr:rowOff>'
                .'</xdr:to>'
                .$this->buildPicXml($relId, $picId)
                .'<xdr:clientData/>'
                .'</xdr:twoCellAnchor>';
        } else {
            $widthEmu = $cell->width * 9525;
            $heightEmu = $cell->height * 9525;
            $anchorXml = '<xdr:oneCellAnchor>'
                .'<xdr:from>'
                .'<xdr:col>'.$columnIndexZeroBased.'</xdr:col><xdr:colOff>0</xdr:colOff>'
                .'<xdr:row>'.$rowIndexZeroBased.'</xdr:row><xdr:rowOff>0</xdr:rowOff>'
                .'</xdr:from>'
                .'<xdr:ext cx="'.$widthEmu.'" cy="'.$heightEmu.'"/>'
                .$this->buildPicXml($relId, $picId, $widthEmu, $heightEmu)
                .'<xdr:clientData/>'
                .'</xdr:oneCellAnchor>';
        }

        fwrite($this->getFilePointer($worksheet), $anchorXml);
    }

    public function hasImages(Worksheet $worksheet): bool
    {
        return isset($this->sheetImages[$worksheet->getId()]);
    }

    /**
     * @return array<string, array{relId: int, extension: string}> [path => drawing relationship]
     */
    public function getImages(Worksheet $worksheet): array
    {
        return $this->sheetImages[$worksheet->getId()] ?? [];
    }

    /**
     * @return array<string, string> [extension => mime type]
     */
    public function getMimeTypes(): array
    {
        return $this->mimeTypes;
    }

    /**
     * Closes the temporary file of the given worksheet. It is reopened in append mode
     * if more images are added later on.
     */
    public function closeFile(Worksheet $worksheet): void
    {
        $sheetId = $worksheet->getId();

        // the runtime may already have freed the handle during shutdown
        if (isset($this->filePointers[$sheetId]) && \is_resource($this->filePointers[$sheetId])) {
            fclose($this->filePointers[$sheetId]);
        }

        unset($this->filePointers[$sheetId]);
    }

    public function getAnchorsFilePath(Worksheet $worksheet): string
    {
        return $this->tempFolder.\DIRECTORY_SEPARATOR.'sheet'.$worksheet->getId().'-drawing.xml';
    }

    /**
     * Builds the <xdr:pic> XML fragment for a single image.
     * For twoCellAnchor the cx/cy in spPr are irrelevant (sizing comes from the cell grid),
     * so they default to 0. For oneCellAnchor they should mirror the <xdr:ext> values so
     * that applications relying on spPr (e.g. LibreOffice) render the image at the correct size.
     */
    private function buildPicXml(int $imageIndex, int $picId, int $widthEmu = 0, int $heightEmu = 0): string
    {
        return '<xdr:pic>'
            .'<xdr:nvPicPr>'
            .'<xdr:cNvPr id="'.$picId.'" name="Image'.$imageIndex.'"/>'
            .'<xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr>'
            .'</xdr:nvPicPr>'
            .'<xdr:blipFill>'
            .'<a:blip r:embed="rId'.$imageIndex.'"/>'
            .'<a:stretch><a:fillRect/></a:stretch>'
            .'</xdr:blipFill>'
            .'<xdr:spPr>'
            .'<a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$widthEmu.'" cy="'.$heightEmu.'"/></a:xfrm>'
            .'<a:prstGeom prst="rect"><a:avLst/></a:prstGeom>'
            .'</xdr:spPr>'
            .'</xdr:pic>';
    }

    /**
     * @return resource
     */
    private function getFilePointer(Worksheet $worksheet)
    {
        $sheetId = $worksheet->getId();

        if (!isset($this->filePointers[$sheetId])) {
            $filePointer = fopen($this->getAnchorsFilePath($worksheet), 'a');
            \assert(false !== $filePointer);
            $this->filePointers[$sheetId] = $filePointer;
        }

        return $this->filePointers[$sheetId];
    }
}
