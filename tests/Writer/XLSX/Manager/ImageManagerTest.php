<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Manager;

use OpenSpout\Common\Entity\Cell\ImageCell;
use OpenSpout\Common\Helper\StringHelper;
use OpenSpout\TestUsingResource;
use OpenSpout\Writer\Common\Entity\Sheet;
use OpenSpout\Writer\Common\Entity\Worksheet;
use OpenSpout\Writer\Common\Manager\SheetManager;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * @internal
 */
final class ImageManagerTest extends TestCase
{
    private string $tempFolder;
    private Worksheet $firstWorksheet;
    private Worksheet $secondWorksheet;
    private ImageManager $imageManager;

    protected function setUp(): void
    {
        $this->tempFolder = (new TestUsingResource())->getTempFolderPath();
        $sheetManager = new SheetManager(StringHelper::factory());
        $workbookId = 'workbook-'.uniqid();
        $this->firstWorksheet = new Worksheet($this->tempFolder.'/sheet1.xml', new Sheet(0, $workbookId, $sheetManager));
        $this->secondWorksheet = new Worksheet($this->tempFolder.'/sheet2.xml', new Sheet(1, $workbookId, $sheetManager));
        $this->imageManager = new ImageManager($this->tempFolder);

        foreach (['a.png', 'b.png', 'c.jpg'] as $fileName) {
            file_put_contents($this->tempFolder.'/'.$fileName, $fileName);
        }
    }

    public function testAnchorsAreWrittenToTheTempFile(): void
    {
        $this->imageManager->addImage($this->secondWorksheet, 0, 0, new ImageCell($this->tempFolder.'/a.png', 10, 20));
        $this->imageManager->addImage($this->secondWorksheet, 2, 3, new ImageCell($this->tempFolder.'/b.png', 5, 5, fitToCell: true));
        $this->imageManager->addImage($this->secondWorksheet, 4, 1, new ImageCell($this->tempFolder.'/a.png', 1, 2));
        $this->imageManager->closeFile($this->secondWorksheet);

        $anchorsFilePath = $this->imageManager->getAnchorsFilePath($this->secondWorksheet);
        self::assertSame($this->tempFolder.\DIRECTORY_SEPARATOR.'sheet2-drawing.xml', $anchorsFilePath);
        self::assertSame(
            '<xdr:oneCellAnchor><xdr:from><xdr:col>0</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:from>'
            .'<xdr:ext cx="95250" cy="190500"/>'.self::picXml(1, 2, 95250, 190500).'<xdr:clientData/></xdr:oneCellAnchor>'
            .'<xdr:twoCellAnchor editAs="twoCell"><xdr:from><xdr:col>3</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>2</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:from>'
            .'<xdr:to><xdr:col>4</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>3</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:to>'
            .self::picXml(2, 3, 0, 0).'<xdr:clientData/></xdr:twoCellAnchor>'
            .'<xdr:oneCellAnchor><xdr:from><xdr:col>1</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>4</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:from>'
            .'<xdr:ext cx="9525" cy="19050"/>'.self::picXml(1, 4, 9525, 19050).'<xdr:clientData/></xdr:oneCellAnchor>',
            file_get_contents($anchorsFilePath)
        );
    }

    public function testImagesAreTrackedPerSheet(): void
    {
        $this->imageManager->addImage($this->firstWorksheet, 0, 0, new ImageCell($this->tempFolder.'/a.png', 1, 1));
        $this->imageManager->addImage($this->firstWorksheet, 1, 0, new ImageCell($this->tempFolder.'/a.png', 1, 1));
        $this->imageManager->addImage($this->firstWorksheet, 2, 0, new ImageCell($this->tempFolder.'/c.jpg', 1, 1));

        self::assertTrue($this->imageManager->hasImages($this->firstWorksheet));
        self::assertFalse($this->imageManager->hasImages($this->secondWorksheet));
        self::assertSame([], $this->imageManager->getImages($this->secondWorksheet));

        $this->imageManager->addImage($this->secondWorksheet, 0, 0, new ImageCell($this->tempFolder.'/c.jpg', 1, 1));
        $this->imageManager->closeFile($this->firstWorksheet);
        $this->imageManager->closeFile($this->secondWorksheet);

        self::assertSame([
            $this->tempFolder.'/a.png' => ['relId' => 1, 'extension' => 'png'],
            $this->tempFolder.'/c.jpg' => ['relId' => 2, 'extension' => 'jpeg'],
        ], $this->imageManager->getImages($this->firstWorksheet));
        self::assertSame([
            $this->tempFolder.'/c.jpg' => ['relId' => 1, 'extension' => 'jpeg'],
        ], $this->imageManager->getImages($this->secondWorksheet));
        self::assertSame(['png' => 'image/png', 'jpeg' => 'image/jpeg'], $this->imageManager->getMimeTypes());
        self::assertStringContainsString('<xdr:cNvPr id="2" name="Image1"/>', (string) file_get_contents($this->imageManager->getAnchorsFilePath($this->secondWorksheet)));
    }

    public function testCloseFileClosesTheFileHandle(): void
    {
        $this->imageManager->addImage($this->firstWorksheet, 0, 0, new ImageCell($this->tempFolder.'/a.png', 1, 1));
        $filePointer = $this->getFilePointer();

        $this->imageManager->closeFile($this->firstWorksheet);

        self::assertFalse(\is_resource($filePointer));
    }

    public function testCloseFileShouldNotThrowTypeErrorOnFreedHandle(): void
    {
        $this->imageManager->addImage($this->firstWorksheet, 0, 0, new ImageCell($this->tempFolder.'/a.png', 1, 1));

        // simulate PHP freeing the file handle during shutdown
        fclose($this->getFilePointer());

        $this->imageManager->closeFile($this->firstWorksheet);

        self::assertTrue($this->imageManager->hasImages($this->firstWorksheet));
    }

    private static function picXml(int $relId, int $picId, int $widthEmu, int $heightEmu): string
    {
        return '<xdr:pic><xdr:nvPicPr><xdr:cNvPr id="'.$picId.'" name="Image'.$relId.'"/><xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr></xdr:nvPicPr>'
            .'<xdr:blipFill><a:blip r:embed="rId'.$relId.'"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill>'
            .'<xdr:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$widthEmu.'" cy="'.$heightEmu.'"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr>'
            .'</xdr:pic>';
    }

    /**
     * @return resource
     */
    private function getFilePointer()
    {
        $filePointers = (new ReflectionProperty(ImageManager::class, 'filePointers'))->getValue($this->imageManager);

        return $filePointers[$this->firstWorksheet->getId()];
    }
}
