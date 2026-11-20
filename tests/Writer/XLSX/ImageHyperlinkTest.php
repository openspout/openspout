<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

use OpenSpout\Common\Entity\Cell\ImageCell;
use OpenSpout\Common\Entity\Hyperlink;
use OpenSpout\Common\Entity\Row;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * @internal
 */
final class ImageHyperlinkTest extends TestCase
{
    private string $testImagePath;

    private string $outputFilePath;

    protected function setUp(): void
    {
        $this->outputFilePath = sys_get_temp_dir().'/test_image_hyperlink.xlsx';
        $this->testImagePath = sys_get_temp_dir().'/openspout_hyperlink_test_image.png';
        file_put_contents($this->testImagePath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwADhQGAWjR9awAAAABJRU5ErkJggg==', true));
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testImagePath)) {
            unlink($this->testImagePath);
        }
        if (file_exists($this->outputFilePath)) {
            unlink($this->outputFilePath);
        }
    }

    public function testExternalHyperlinkOnImage(): void
    {
        [$drawing, $rels] = $this->writeAndRead(new ImageCell($this->testImagePath, 10, 10, null, null, false, Hyperlink::toUrl('https://example.com/?a=1&b=2')->withTooltip('Open')));

        self::assertStringContainsString('<xdr:cNvPr id="2" name="Image1"><a:hlinkClick r:id="rId_hyperlink1" tooltip="Open"/></xdr:cNvPr>', $drawing);
        self::assertStringContainsString('Id="rId_hyperlink1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink" Target="https://example.com/?a=1&amp;b=2" TargetMode="External"', $rels);
        self::assertStringContainsString('Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image"', $rels);
    }

    public function testInternalHyperlinkOnImageHasNoTargetMode(): void
    {
        [, $rels] = $this->writeAndRead((new ImageCell($this->testImagePath, 10, 10))->withHyperlink(Hyperlink::toCell('B2', 'Sheet1')));

        self::assertStringContainsString("Target=\"#'Sheet1'!B2\"/>", $rels);
        self::assertStringNotContainsString('TargetMode', $rels);
    }

    public function testImageWithoutHyperlinkIsUnchanged(): void
    {
        [$drawing, $rels] = $this->writeAndRead(new ImageCell($this->testImagePath, 10, 10));

        self::assertStringContainsString('<xdr:cNvPr id="2" name="Image1"/>', $drawing);
        self::assertStringNotContainsString('hyperlink', $rels);
    }

    public function testHyperlinkSurvivesWithMethods(): void
    {
        $cell = (new ImageCell($this->testImagePath, 10, 10, null, null, false, 'https://example.com'))->withFitToCell(true);
        self::assertSame('https://example.com', (string) $cell->hyperlink);
        self::assertNull($cell->withoutHyperlink()->hyperlink);
    }

    /**
     * @return array{string, string} drawing XML, drawing rels XML
     */
    private function writeAndRead(ImageCell $cell): array
    {
        $writer = new Writer();
        $writer->openToFile($this->outputFilePath);
        $writer->addRow(new Row([$cell]));
        $writer->close();

        $zip = new ZipArchive();
        self::assertTrue($zip->open($this->outputFilePath));
        $drawing = (string) $zip->getFromName('xl/drawings/drawing1.xml');
        $rels = (string) $zip->getFromName('xl/drawings/_rels/drawing1.xml.rels');
        $zip->close();

        return [$drawing, $rels];
    }
}
