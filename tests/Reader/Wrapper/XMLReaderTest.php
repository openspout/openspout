<?php

namespace OpenSpout\Reader\Wrapper;

use OpenSpout\Reader\Exception\XMLProcessingException;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class XMLReaderTest extends TestCase
{
    use TestUsingResource;

    public function testOpenShouldFailIfFileInsideZipDoesNotExist()
    {
        $resourcePath = $this->getResourcePath('one_sheet_with_inline_strings.xlsx');

        $xmlReader = new XMLReader();

        // using "@" to prevent errors/warning to be displayed
        $wasOpenSuccessful = @$xmlReader->openFileInZip($resourcePath, 'path/to/fake/file.xml');

        static::assertFalse($wasOpenSuccessful);
    }

    /**
     * Testing a HHVM bug: https://github.com/facebook/hhvm/issues/5779
     * The associated code in XMLReader::open() can be removed when the issue is fixed (and this test starts failing).
     *
     * @see XMLReader::open()
     */
    public function testHHVMStillDoesNotComplainWhenCallingOpenWithFileInsideZipNotExisting()
    {
        // Test should only be run on HHVM
        if ($this->isRunningHHVM()) {
            $resourcePath = $this->getResourcePath('one_sheet_with_inline_strings.xlsx');
            $nonExistingXMLFilePath = 'zip://'.$resourcePath.'#path/to/fake/file.xml';

            libxml_clear_errors();
            $initialUseInternalErrorsSetting = libxml_use_internal_errors(true);

            // using the built-in XMLReader
            $xmlReader = new \XMLReader();
            static::assertNotFalse($xmlReader->open($nonExistingXMLFilePath));
            static::assertFalse(libxml_get_last_error());

            libxml_use_internal_errors($initialUseInternalErrorsSetting);
        } else {
            static::markTestSkipped();
        }
    }

    public function testReadShouldThrowExceptionOnError()
    {
        $this->expectException(XMLProcessingException::class);

        $resourcePath = $this->getResourcePath('one_sheet_with_invalid_xml_characters.xlsx');

        $xmlReader = new XMLReader();
        if (false === $xmlReader->openFileInZip($resourcePath, 'xl/worksheets/sheet1.xml')) {
            static::fail();
        }

        // using "@" to prevent errors/warning to be displayed
        while (@$xmlReader->read());
        // do nothing
    }

    public function testNextShouldThrowExceptionOnError()
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

    /**
     * @return array
     */
    public function dataProviderForTestFileExistsWithinZip()
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
     *
     * @param string $innerFilePath
     * @param bool   $expectedResult
     */
    public function testFileExistsWithinZip($innerFilePath, $expectedResult)
    {
        $resourcePath = $this->getResourcePath('one_sheet_with_inline_strings.xlsx');
        $zipStreamURI = 'zip://'.$resourcePath.'#'.$innerFilePath;

        $xmlReader = new XMLReader();
        $isZipStream = \ReflectionHelper::callMethodOnObject($xmlReader, 'fileExistsWithinZip', $zipStreamURI);

        static::assertSame($expectedResult, $isZipStream);
    }

    /**
     * @return array
     */
    public function dataProviderForTestGetRealPathURIForFileInZip()
    {
        $tempFolder = realpath(sys_get_temp_dir());
        $tempFolderName = basename($tempFolder);
        $expectedRealPathURI = 'zip://'.$tempFolder.'/test.xlsx#test.xml';

        return [
            [$tempFolder, "{$tempFolder}/test.xlsx", 'test.xml', $expectedRealPathURI],
            [$tempFolder, "{$tempFolder}/../{$tempFolderName}/test.xlsx", 'test.xml', $expectedRealPathURI],
        ];
    }

    /**
     * @dataProvider dataProviderForTestGetRealPathURIForFileInZip
     *
     * @param string $tempFolder
     * @param string $zipFilePath
     * @param string $fileInsideZipPath
     * @param string $expectedRealPathURI
     */
    public function testGetRealPathURIForFileInZip($tempFolder, $zipFilePath, $fileInsideZipPath, $expectedRealPathURI)
    {
        touch($tempFolder.'/test.xlsx');

        $xmlReader = new XMLReader();
        $realPathURI = \ReflectionHelper::callMethodOnObject($xmlReader, 'getRealPathURIForFileInZip', $zipFilePath, $fileInsideZipPath);

        // Normalizing path separators for Windows support
        $normalizedRealPathURI = str_replace('\\', '/', $realPathURI);
        $normalizedExpectedRealPathURI = str_replace('\\', '/', $expectedRealPathURI);

        static::assertSame($normalizedExpectedRealPathURI, $normalizedRealPathURI);

        unlink($tempFolder.'/test.xlsx');
    }

    /**
     * @return array
     */
    public function dataProviderForTestIsPositionedOnStartingAndEndingNode()
    {
        return [
            ['<test></test>'], // not prefixed
            ['<x:test xmlns:x="foo"></x:test>'], // prefixed
        ];
    }

    /**
     * @dataProvider dataProviderForTestIsPositionedOnStartingAndEndingNode
     *
     * @param string $testXML
     */
    public function testIsPositionedOnStartingAndEndingNode($testXML)
    {
        $xmlReader = new XMLReader();
        $xmlReader->XML($testXML);

        // the first read moves the pointer to "<test>"
        $xmlReader->read();
        static::assertTrue($xmlReader->isPositionedOnStartingNode('test'));
        static::assertFalse($xmlReader->isPositionedOnEndingNode('test'));

        // the seconds read moves the pointer to "</test>"
        $xmlReader->read();
        static::assertFalse($xmlReader->isPositionedOnStartingNode('test'));
        static::assertTrue($xmlReader->isPositionedOnEndingNode('test'));

        $xmlReader->close();
    }

    /**
     * @return bool TRUE if running on HHVM, FALSE otherwise
     */
    private function isRunningHHVM()
    {
        return \defined('HHVM_VERSION');
    }
}
