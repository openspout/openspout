<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Manager;

use OpenSpout\Common\Entity\Comment\TextRun;
use OpenSpout\Common\Entity\Style\TextRunVerticalStyle;
use OpenSpout\Common\Helper\Escaper;

/**
 * @internal
 */
final class SharedStringsManager
{
    public const string SHARED_STRINGS_FILE_NAME = 'sharedStrings.xml';

    public const string SHARED_STRINGS_XML_FILE_FIRST_PART_HEADER = <<<'EOD'
        <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
        EOD;

    /**
     * This number must be huge so that the no generated file will have more strings than that.
     * If the strings number goes above, characters will be overwritten in an unwanted way and will corrupt the file.
     */
    public const string DEFAULT_STRINGS_COUNT_PART = 'count="9999999999999" uniqueCount="9999999999999"';

    /** @var resource Pointer to the sharedStrings.xml file */
    private $sharedStringsFilePointer;

    /** @var int Number of shared strings already written */
    private int $numSharedStrings = 0;

    /** @var Escaper\XLSX Strings escaper */
    private readonly Escaper\XLSX $stringsEscaper;

    /**
     * @param string       $xlFolder       Path to the "xl" folder
     * @param Escaper\XLSX $stringsEscaper Strings escaper
     */
    public function __construct(string $xlFolder, Escaper\XLSX $stringsEscaper)
    {
        $sharedStringsFilePath = $xlFolder.\DIRECTORY_SEPARATOR.self::SHARED_STRINGS_FILE_NAME;
        $resource = fopen($sharedStringsFilePath, 'w');
        \assert(false !== $resource);
        $this->sharedStringsFilePointer = $resource;

        // the headers is split into different parts so that we can fseek and put in the correct count and uniqueCount later
        $header = self::SHARED_STRINGS_XML_FILE_FIRST_PART_HEADER.' '.self::DEFAULT_STRINGS_COUNT_PART.'>';
        fwrite($this->sharedStringsFilePointer, $header);

        $this->stringsEscaper = $stringsEscaper;
    }

    /**
     * Writes the given string into the sharedStrings.xml file.
     * Starting and ending whitespaces are preserved.
     *
     * @return int ID of the written shared string
     */
    public function writeString(string $string): int
    {
        fwrite($this->sharedStringsFilePointer, '<si><t xml:space="preserve">'.$this->stringsEscaper->escape($string).'</t></si>');
        ++$this->numSharedStrings;

        // Shared string ID is zero-based
        return $this->numSharedStrings - 1;
    }

    /**
     * Writes the given string with text run formatting into the sharedStrings.xml file.
     * Starting and ending whitespaces are preserved.
     *
     * @param TextRun[] $textRuns
     *
     * @return int ID of the written shared string
     */
    public function writeTextRuns(array $textRuns): int
    {
        fwrite($this->sharedStringsFilePointer, '<si>');
        foreach ($textRuns as $textRun) {
            $this->writeTextRunString($textRun);
        }
        fwrite($this->sharedStringsFilePointer, '</si>');
        ++$this->numSharedStrings;

        // Shared string ID is zero-based
        return $this->numSharedStrings - 1;
    }

    /**
     * Finishes writing the data in the sharedStrings.xml file and closes the file.
     */
    public function close(): void
    {
        fwrite($this->sharedStringsFilePointer, '</sst>');

        // Replace the default strings count with the actual number of shared strings in the file header
        $firstPartHeaderLength = \strlen(self::SHARED_STRINGS_XML_FILE_FIRST_PART_HEADER);
        $defaultStringsCountPartLength = \strlen(self::DEFAULT_STRINGS_COUNT_PART);

        // Adding 1 to take into account the space between the last xml attribute and "count"
        fseek($this->sharedStringsFilePointer, $firstPartHeaderLength + 1);
        fwrite($this->sharedStringsFilePointer, \sprintf("%-{$defaultStringsCountPartLength}s", 'count="'.$this->numSharedStrings.'" uniqueCount="'.$this->numSharedStrings.'"'));

        fclose($this->sharedStringsFilePointer);
    }

    /**
     * Writes the given text run string into the sharedStrings.xml file.
     */
    private function writeTextRunString(TextRun $textRun): void
    {
        $propertiesXml = '<rPr>';
        if ($textRun->bold) {
            $propertiesXml .= '<b/>';
        }

        if ($textRun->italic) {
            $propertiesXml .= '<i/>';
        }

        if (TextRunVerticalStyle::SUBSCRIPT === $textRun->verticalStyle) {
            $propertiesXml .= '<vertAlign val="subscript"/>';
        } elseif (TextRunVerticalStyle::SUPERSCRIPT === $textRun->verticalStyle) {
            $propertiesXml .= '<vertAlign val="superscript"/>';
        }

        if (TextRun::DEFAULT_FONT_SIZE !== $textRun->fontSize) {
            $propertiesXml .= '<sz val="'.$textRun->fontSize.'"/>';
        }

        if (TextRun::DEFAULT_FONT_COLOR !== $textRun->fontColor) {
            $propertiesXml .= '<color rgb="'.$textRun->fontColor.'"/>';
        }

        if (TextRun::DEFAULT_FONT_NAME !== $textRun->fontName) {
            $propertiesXml .= '<rFont val="'.$textRun->fontName.'"/>';
        }
        $propertiesXml .= '</rPr>';

        fwrite($this->sharedStringsFilePointer, '<r>'.$propertiesXml.'<t xml:space="preserve">'.$this->stringsEscaper->escape($textRun->text).'</t></r>');
    }
}
