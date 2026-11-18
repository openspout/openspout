<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Manager;

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Helper\Escaper\XLSX as XLSXEscaper;
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
final class HyperlinkManagerTest extends TestCase
{
    private string $tempFolder;
    private Worksheet $worksheet;
    private HyperlinkManager $hyperlinkManager;

    protected function setUp(): void
    {
        $this->tempFolder = (new TestUsingResource())->getTempFolderPath();
        $sheet = new Sheet(1, 'workbook-'.uniqid(), new SheetManager(StringHelper::factory()));
        $this->worksheet = new Worksheet($this->tempFolder.'/sheet2.xml', $sheet);
        $this->hyperlinkManager = new HyperlinkManager($this->tempFolder, new XLSXEscaper());
    }

    public function testFilesAreWrittenToTheTempFolder(): void
    {
        $this->addHyperlink();

        self::assertSame($this->tempFolder.\DIRECTORY_SEPARATOR.'sheet2-hyperlinks.xml', $this->hyperlinkManager->getHyperlinksFilePath($this->worksheet));
        self::assertSame($this->tempFolder.\DIRECTORY_SEPARATOR.'sheet2-hyperlinks.rels', $this->hyperlinkManager->getRelationshipsFilePath($this->worksheet));
        self::assertFileExists($this->hyperlinkManager->getHyperlinksFilePath($this->worksheet));
        self::assertFileExists($this->hyperlinkManager->getRelationshipsFilePath($this->worksheet));
    }

    public function testCloseFilesClosesTheFileHandles(): void
    {
        $this->addHyperlink();
        $filePointers = $this->getFilePointers();

        $this->hyperlinkManager->closeFiles($this->worksheet);

        foreach ($filePointers as $filePointer) {
            self::assertFalse(\is_resource($filePointer));
        }
    }

    public function testCloseFilesShouldNotThrowTypeErrorOnFreedHandles(): void
    {
        $this->addHyperlink();

        // simulate PHP freeing the file handles during shutdown
        foreach ($this->getFilePointers() as $filePointer) {
            fclose($filePointer);
        }

        $this->hyperlinkManager->closeFiles($this->worksheet);

        self::assertTrue($this->hyperlinkManager->hasHyperlinks($this->worksheet));
    }

    private function addHyperlink(): void
    {
        $this->hyperlinkManager->addHyperlinks($this->worksheet, new Row([Cell::fromValue('link', null, null, 'https://example.com')]));
    }

    /**
     * @return array{resource, resource}
     */
    private function getFilePointers(): array
    {
        $filePointers = (new ReflectionProperty(HyperlinkManager::class, 'filePointers'))->getValue($this->hyperlinkManager);

        return $filePointers[$this->worksheet->getId()];
    }
}
