<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Hyperlink;
use OpenSpout\Common\Entity\Row;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * @internal
 */
final class HyperlinkTest extends TestCase
{
    private string $outputFilePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outputFilePath = sys_get_temp_dir().'/test_hyperlink.xlsx';
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

    public function testLinksWithinTheWorkbookHaveNoRelationship(): void
    {
        $writer = new Writer(new Options(tempFolder: (new TestUsingResource())->getTempFolderPath()));
        $writer->openToFile($this->outputFilePath);

        $writer->addRow(new Row([
            Cell::fromValue('totals', null, null, Hyperlink::toCell('A100')),
            Cell::fromValue('website', null, null, 'https://example.com/a'),
            Cell::fromValue('details', null, null, Hyperlink::toCell('B2:D10', 'Orders & "Returns"')),
            Cell::fromValue('website', null, null, Hyperlink::toUrl('https://example.com/b')),
        ]));

        $writer->addNewSheetAndMakeItCurrent();
        $writer->addRow(new Row([Cell::fromValue('back', null, null, Hyperlink::toSheet('Sheet1'))]));

        $writer->close();

        $zip = new ZipArchive();
        self::assertTrue($zip->open($this->outputFilePath));

        self::assertStringContainsString(
            '<hyperlinks>'
            .'<hyperlink ref="A1" location="A100"/>'
            .'<hyperlink ref="B1" r:id="rId_hyperlink1"/>'
            .'<hyperlink ref="C1" location="\'Orders &amp; &quot;Returns&quot;\'!B2:D10"/>'
            .'<hyperlink ref="D1" r:id="rId_hyperlink2"/>'
            .'</hyperlinks>',
            (string) $zip->getFromName('xl/worksheets/sheet1.xml')
        );
        self::assertStringContainsString(
            '<hyperlinks><hyperlink ref="A1" location="\'Sheet1\'!A1"/></hyperlinks>',
            (string) $zip->getFromName('xl/worksheets/sheet2.xml')
        );

        $firstSheetRels = (string) $zip->getFromName('xl/worksheets/_rels/sheet1.xml.rels');
        self::assertSame(2, substr_count($firstSheetRels, 'relationships/hyperlink'));
        self::assertStringNotContainsString('#', $firstSheetRels);

        $secondSheetRels = simplexml_load_string((string) $zip->getFromName('xl/worksheets/_rels/sheet2.xml.rels'));
        self::assertNotFalse($secondSheetRels);
        self::assertStringNotContainsString('relationships/hyperlink', (string) $secondSheetRels->asXML());

        $zip->close();
    }

    public function testHyperlinksAreKeptPerSheetWhenSwitchingSheets(): void
    {
        $writer = new Writer(new Options(tempFolder: (new TestUsingResource())->getTempFolderPath()));
        $writer->openToFile($this->outputFilePath);

        $firstSheet = $writer->getCurrentSheet();
        $writer->addRow(new Row([
            Cell::fromValue('a', null, null, 'https://example.com/a'),
            Cell::fromValue('no link'),
            Cell::fromValue('b', null, null, 'https://example.com/b?x=1&y="2"'),
        ]));

        $writer->addNewSheetAndMakeItCurrent();
        $writer->addRow(new Row([Cell::fromValue('no link'), Cell::fromValue('c', null, null, 'https://example.com/c')]));

        $writer->addNewSheetAndMakeItCurrent();
        $writer->addRow(Row::fromValues(['no links on this sheet']));

        $writer->setCurrentSheet($firstSheet);
        $writer->addRow(new Row([Cell::fromValue('d', null, null, 'https://example.com/d')]));

        $writer->setCurrentSheet($writer->getSheets()[1]);
        $writer->addRow(new Row([Cell::fromValue('e', null, null, 'https://example.com/e')]));

        $writer->close();

        $zip = new ZipArchive();
        self::assertTrue($zip->open($this->outputFilePath));

        self::assertStringContainsString(
            '<hyperlinks><hyperlink ref="A1" r:id="rId_hyperlink1"/><hyperlink ref="C1" r:id="rId_hyperlink2"/><hyperlink ref="A2" r:id="rId_hyperlink3"/></hyperlinks>',
            (string) $zip->getFromName('xl/worksheets/sheet1.xml')
        );
        self::assertStringContainsString(
            '<hyperlinks><hyperlink ref="B1" r:id="rId_hyperlink1"/><hyperlink ref="A2" r:id="rId_hyperlink2"/></hyperlinks>',
            (string) $zip->getFromName('xl/worksheets/sheet2.xml')
        );
        self::assertStringNotContainsString('<hyperlinks>', (string) $zip->getFromName('xl/worksheets/sheet3.xml'));

        $relationships = [];
        for ($sheetId = 1; $sheetId <= 3; ++$sheetId) {
            $relsXml = simplexml_load_string((string) $zip->getFromName("xl/worksheets/_rels/sheet{$sheetId}.xml.rels"));
            self::assertNotFalse($relsXml);
            foreach ($relsXml->Relationship as $relationship) {
                if (str_starts_with((string) $relationship['Id'], 'rId_hyperlink')) {
                    $relationships[$sheetId][(string) $relationship['Id']] = (string) $relationship['Target'];
                }
            }
        }

        self::assertSame([
            1 => [
                'rId_hyperlink1' => 'https://example.com/a',
                'rId_hyperlink2' => 'https://example.com/b?x=1&y="2"',
                'rId_hyperlink3' => 'https://example.com/d',
            ],
            2 => [
                'rId_hyperlink1' => 'https://example.com/c',
                'rId_hyperlink2' => 'https://example.com/e',
            ],
        ], $relationships);

        for ($i = 0; $i < $zip->numFiles; ++$i) {
            self::assertStringNotContainsString('hyperlinks', (string) $zip->getNameIndex($i));
        }

        $zip->close();
    }

    public function testHyperlinkFilesAreClosedWhenSwitchingSheetsAndOnClose(): void
    {
        if (!is_dir('/proc/self/fd')) {
            self::markTestSkipped('This test requires Linux with /proc/self/fd');
        }

        $countFds = static function (): int {
            $fds = scandir('/proc/self/fd');
            \assert(false !== $fds);

            return \count($fds);
        };

        $fdsBeforeOpen = $countFds();

        $writer = new Writer(new Options(tempFolder: (new TestUsingResource())->getTempFolderPath()));
        $writer->openToFile($this->outputFilePath);
        $writer->addRow(new Row([Cell::fromValue('link', null, null, 'https://example.com')]));

        $fdsWithOneSheet = $countFds();

        for ($i = 0; $i < 5; ++$i) {
            $writer->addNewSheetAndMakeItCurrent();
            $writer->addRow(new Row([Cell::fromValue('link', null, null, 'https://example.com')]));
        }

        self::assertSame($fdsWithOneSheet, $countFds());

        $writer->close();

        self::assertSame($fdsBeforeOpen, $countFds());
    }

    private function assertZipFileContainsHyperlink(string $filePath, string $cellRef, string $url): void
    {
        $zip = new ZipArchive();
        $zip->open($filePath);

        // Check sheet1.xml
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        self::assertNotFalse($sheetXml);
        self::assertStringContainsString('<hyperlinks>', $sheetXml);
        self::assertStringContainsString('<hyperlink ref="'.$cellRef.'" r:id="rId_hyperlink1"/>', $sheetXml);

        // Check sheet1.xml.rels
        $relsXml = $zip->getFromName('xl/worksheets/_rels/sheet1.xml.rels');
        self::assertNotFalse($relsXml);
        self::assertStringContainsString('Id="rId_hyperlink1"', $relsXml);
        self::assertStringContainsString('Target="'.$url.'"', $relsXml);
        self::assertStringContainsString('TargetMode="External"', $relsXml);
        self::assertStringContainsString('Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink"', $relsXml);

        $zip->close();
    }
}
