<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

use PHPUnit\Framework\TestCase;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\TestHelper\ReflectionHelper;
use ZipArchive;

final class HyperlinkTest extends TestCase
{
    private string $outputFilePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outputFilePath = sys_get_temp_dir() . '/test_hyperlink.xlsx';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->outputFilePath)) {
            unlink($this->outputFilePath);
        }
    }

    public function testAddHyperlink(): void
    {
        $writer = new Writer();
        $writer->openToFile($this->outputFilePath);

        $cell = Cell::fromValue('Example', null, null, 'https://example.com');
        $row = new Row([$cell]);

        $writer->addRow($row);
        $writer->close();

        $this->assertZipFileContainsHyperlink($this->outputFilePath, 'A1', 'https://example.com');
    }

    private function assertZipFileContainsHyperlink(string $filePath, string $cellRef, string $url): void
    {
        $zip = new ZipArchive();
        $zip->open($filePath);

        // Check sheet1.xml
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertStringContainsString('<hyperlinks>', $sheetXml);
        $this->assertStringContainsString('<hyperlink ref="' . $cellRef . '" r:id="rId_hyperlink1"/>', $sheetXml);

        // Check sheet1.xml.rels
        $relsXml = $zip->getFromName('xl/worksheets/_rels/sheet1.xml.rels');
        $this->assertStringContainsString('Id="rId_hyperlink1"', $relsXml);
        $this->assertStringContainsString('Target="' . $url . '"', $relsXml);
        $this->assertStringContainsString('TargetMode="External"', $relsXml);
        $this->assertStringContainsString('Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink"', $relsXml);

        $zip->close();
    }
}
