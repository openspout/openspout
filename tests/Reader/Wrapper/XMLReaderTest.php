<?php

declare(strict_types=1);

namespace OpenSpout\Reader\Wrapper;

use OpenSpout\Reader\Exception\XMLProcessingException;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;
use ReflectionHelper;

/**
 * @internal
 */
final class XMLReaderTest extends TestCase
{
    use TestUsingResource;

    public function testOpenShouldFailIfFileInsideZipDoesNotExist(): void
    {
        $resourcePath = $this->getResourcePath('one_sheet_with_inline_strings.xlsx');

        $xmlReader = new XMLReader();

        // using "@" to prevent errors/warning to be displayed
        $wasOpenSuccessful = @$xmlReader->openFileInZip($resourcePath, 'path/to/fake/file.xml');

        self::assertFalse($wasOpenSuccessful);
    }

    /**
     * Testing a HHVM bug: https://github.com/facebook/hhvm/issues/5779
     * The associated code in XMLReader::open() can be removed when the issue is fixed (and this test starts failing).
     *
     * @see XMLReader::open()
     */
    public function testHHVMStillDoesNotComplainWhenCallingOpenWithFileInsideZipNotExisting(): void
    {
        // Test should only be run on HHVM
        if ($this->isRunningHHVM()) {
            $resourcePath = $this->getResourcePath('one_sheet_with_inline_strings.xlsx');
            $nonExistingXMLFilePath = 'zip://'.$resourcePath.'#path/to/fake/file.xml';

            libxml_clear_errors();
            $initialUseInternalErrorsSetting = libxml_use_internal_errors(true);

            self::assertNotFalse(\XMLReader::open($nonExistingXMLFilePath));
            self::assertFalse(libxml_get_last_error());

            libxml_use_internal_errors($initialUseInternalErrorsSetting);
        } else {
            self::markTestSkipped();
        }
    }

    public function testReadShouldThrowExceptionOnError(): void
    {
        $this->expectException(XMLProcessingException::class);

        $resourcePath = $this->getResourcePath('one_sheet_with_invalid_xml_characters.xlsx');

        $xmlReader = new XMLReader();
        if (false === $xmlReader->openFileInZip($resourcePath, 'xl/worksheets/sheet1.xml')) {
            self::fail();
        }

        // using "@" to prevent errors/warning to be displayed
        while (@$xmlReader->read());
        // do nothing
    }

    public function testNextShouldThrowExceptionOnError(): void
    {
        $this->expectException(XMLProcessingException::class);

        // The sharedStrings.xml file in "attack_billion_laughs.xlsx" contains
        // a doctype element that causes read errors
        $resourcePath = $this->getResourcePath('attack_billion_laughs.xlsx');

        $xmlReader = new XMLReader();
        if (false !== $xmlReader->openFileInZip($resourcePath, 'xl/sharedStrings.xml')) {
            @$xmlReader->next('sst');
        }
    }

    public function dataProviderForTestFileExistsWithinZip(): array
    {
        return [
            ['[Content_Types].xml', true],
            ['xl/sharedStrings.xml', true],
            ['xl/worksheets/sheet1.xml', true],
            ['/invalid/file.xml', false],
            ['another/invalid/file.xml', false],
        ];
    }

    /**
     * @dataProvider dataProviderForTestFileExistsWithinZip
     */
    public function testFileExistsWithinZip(string $innerFilePath, bool $expectedResult): void
    {
        $resourcePath = $this->getResourcePath('one_sheet_with_inline_strings.xlsx');
        $zipStreamURI = 'zip://'.$resourcePath.'#'.$innerFilePath;

        $xmlReader = new XMLReader();
        $isZipStream = ReflectionHelper::callMethodOnObject($xmlReader, 'fileExistsWithinZip', $zipStreamURI);

        self::assertSame($expectedResult, $isZipStream);
    }

    public function dataProviderForTestGetRealPathURIForFileInZip(): array
    {
        $tempFolder = realpath(sys_get_temp_dir());
        self::assertNotFalse($tempFolder);
        $tempFolderName = basename($tempFolder);
        $expectedRealPathURI = 'zip://'.$tempFolder.'/test.xlsx#test.xml';

        return [
            [$tempFolder, "{$tempFolder}/test.xlsx", 'test.xml', $expectedRealPathURI],
            [$tempFolder, "{$tempFolder}/../{$tempFolderName}/test.xlsx", 'test.xml', $expectedRealPathURI],
        ];
    }

    /**
     * @dataProvider dataProviderForTestGetRealPathURIForFileInZip
     */
    public function testGetRealPathURIForFileInZip(string $tempFolder, string $zipFilePath, string $fileInsideZipPath, string $expectedRealPathURI): void
    {
        touch($tempFolder.'/test.xlsx');

        $xmlReader = new XMLReader();
        $realPathURI = ReflectionHelper::callMethodOnObject($xmlReader, 'getRealPathURIForFileInZip', $zipFilePath, $fileInsideZipPath);

        // Normalizing path separators for Windows support
        $normalizedRealPathURI = str_replace('\\', '/', $realPathURI);
        $normalizedExpectedRealPathURI = str_replace('\\', '/', $expectedRealPathURI);

        self::assertSame($normalizedExpectedRealPathURI, $normalizedRealPathURI);

        unlink($tempFolder.'/test.xlsx');
    }

    public function dataProviderForTestIsPositionedOnStartingAndEndingNode(): array
    {
        return [
            ['<test></test>'], // not prefixed
            ['<x:test xmlns:x="foo"></x:test>'], // prefixed
        ];
    }

    /**
     * @dataProvider dataProviderForTestIsPositionedOnStartingAndEndingNode
     */
    public function testIsPositionedOnStartingAndEndingNode(string $testXML): void
    {
        $xmlReader = new XMLReader();
        $xmlReader->XML($testXML);

        // the first read moves the pointer to "<test>"
        $xmlReader->read();
        self::assertTrue($xmlReader->isPositionedOnStartingNode('test'));
        self::assertFalse($xmlReader->isPositionedOnEndingNode('test'));

        // the seconds read moves the pointer to "</test>"
        $xmlReader->read();
        self::assertFalse($xmlReader->isPositionedOnStartingNode('test'));
        self::assertTrue($xmlReader->isPositionedOnEndingNode('test'));

        $xmlReader->close();
    }

    /**
     * @return bool TRUE if running on HHVM, FALSE otherwise
     */
    private function isRunningHHVM(): bool
    {
        return \defined('HHVM_VERSION');
    }
}
