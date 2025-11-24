<?php

declare(strict_types=1);

namespace OpenSpout\Common\Helper\Escaper;

/**
 * @internal
 */
final readonly class XLSX implements EscaperInterface
{
    /**
     * Regex pattern to detect control characters that need to be escaped.
     */
    private const string escapableControlCharactersPattern
        = '[\x00-\x08'
        // skipping "\t" (0x9) and "\n" (0xA)
        .'\x0B-\x0C'
        // skipping "\r" (0xD)
        .'\x0E-\x1F]'
    ;

    /**
     * Map containing control characters to be escaped (key) and their escaped value (value).
     */
    private const array controlCharactersEscapingMap = [
        '_x0000_' => "\x00",
        '_x0001_' => "\x01",
        '_x0002_' => "\x02",
        '_x0003_' => "\x03",
        '_x0004_' => "\x04",
        '_x0005_' => "\x05",
        '_x0006_' => "\x06",
        '_x0007_' => "\x07",
        '_x0008_' => "\x08",
        '_x000B_' => "\x0B",
        '_x000C_' => "\x0C",
        '_x000E_' => "\x0E",
        '_x000F_' => "\x0F",
        '_x0010_' => "\x10",
        '_x0011_' => "\x11",
        '_x0012_' => "\x12",
        '_x0013_' => "\x13",
        '_x0014_' => "\x14",
        '_x0015_' => "\x15",
        '_x0016_' => "\x16",
        '_x0017_' => "\x17",
        '_x0018_' => "\x18",
        '_x0019_' => "\x19",
        '_x001A_' => "\x1A",
        '_x001B_' => "\x1B",
        '_x001C_' => "\x1C",
        '_x001D_' => "\x1D",
        '_x001E_' => "\x1E",
        '_x001F_' => "\x1F",
    ];

    /**
     * Map containing control characters to be escaped (value) and their escaped value (key).
     */
    private const array controlCharactersEscapingReverseMap = [
        "\x00" => '_x0000_',
        "\x01" => '_x0001_',
        "\x02" => '_x0002_',
        "\x03" => '_x0003_',
        "\x04" => '_x0004_',
        "\x05" => '_x0005_',
        "\x06" => '_x0006_',
        "\x07" => '_x0007_',
        "\x08" => '_x0008_',
        "\x0B" => '_x000B_',
        "\x0C" => '_x000C_',
        "\x0E" => '_x000E_',
        "\x0F" => '_x000F_',
        "\x10" => '_x0010_',
        "\x11" => '_x0011_',
        "\x12" => '_x0012_',
        "\x13" => '_x0013_',
        "\x14" => '_x0014_',
        "\x15" => '_x0015_',
        "\x16" => '_x0016_',
        "\x17" => '_x0017_',
        "\x18" => '_x0018_',
        "\x19" => '_x0019_',
        "\x1A" => '_x001A_',
        "\x1B" => '_x001B_',
        "\x1C" => '_x001C_',
        "\x1D" => '_x001D_',
        "\x1E" => '_x001E_',
        "\x1F" => '_x001F_',
    ];

    /**
     * Escapes the given string to make it compatible with XLSX.
     *
     * @param string $string The string to escape
     *
     * @return string The escaped string
     */
    public function escape(string $string): string
    {
        $escapedString = $this->escapeControlCharacters($string);

        // @NOTE: Using ENT_QUOTES as XML entities ('<', '>', '&') as well as
        //        single/double quotes (for XML attributes) need to be encoded.
        return htmlspecialchars($escapedString, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Unescapes the given string to make it compatible with XLSX.
     *
     * @param string $string The string to unescape
     *
     * @return string The unescaped string
     */
    public function unescape(string $string): string
    {
        // ==============
        // =   WARNING  =
        // ==============
        // It is assumed that the given string has already had its XML entities decoded.
        // This is true if the string is coming from a DOMNode (as DOMNode already decode XML entities on creation).
        // Therefore, there is no need to call "htmlspecialchars_decode()".
        return $this->unescapeControlCharacters($string);
    }

    /*
     * Builds the map containing control characters to be escaped
     * mapped to their escaped values.
     * "\t", "\r" and "\n" don't need to be escaped.
     *
     * NOTE: the logic has been adapted from the XlsxWriter library (BSD License)
     *
     * @see https://github.com/jmcnamara/XlsxWriter/blob/f1e610f29/xlsxwriter/sharedstrings.py#L89
     *
     * @return string[]
     */
    /*
    private function getControlCharactersEscapingMap(): array
    {
        $controlCharactersEscapingMap = [];

        // control characters values are from 0 to 1F (hex values) in the ASCII table
        for ($charValue = 0x00; $charValue <= 0x1F; ++$charValue) {
            $character = \chr($charValue);
            if (1 === preg_match("/{$this->escapableControlCharactersPattern}/", $character)) {
                $charHexValue = dechex($charValue);
                $escapedChar = '_x'.\sprintf('%04s', strtoupper($charHexValue)).'_';
                $controlCharactersEscapingMap[$escapedChar] = $character;
            }
        }

        return $controlCharactersEscapingMap;
    }
    */

    /**
     * Converts PHP control characters from the given string to OpenXML escaped control characters.
     *
     * Excel escapes control characters with _xHHHH_ and also escapes any
     * literal strings of that type by encoding the leading underscore.
     * So "\0" -> _x0000_ and "_x0000_" -> _x005F_x0000_.
     *
     * NOTE: the logic has been adapted from the XlsxWriter library (BSD License)
     *
     * @see https://github.com/jmcnamara/XlsxWriter/blob/f1e610f29/xlsxwriter/sharedstrings.py#L89
     *
     * @param string $string String to escape
     */
    private function escapeControlCharacters(string $string): string
    {
        $escapedString = $this->escapeEscapeCharacter($string);

        // if no control characters
        if (1 !== preg_match('/'.self::escapableControlCharactersPattern.'/', $escapedString)) {
            return $escapedString;
        }

        return preg_replace_callback('/('.self::escapableControlCharactersPattern.')/', static function ($matches): string {
            return self::controlCharactersEscapingReverseMap[$matches[0]];
        }, $escapedString);
    }

    /**
     * Escapes the escape character: "_x0000_" -> "_x005F_x0000_".
     *
     * @param string $string String to escape
     *
     * @return string The escaped string
     */
    private function escapeEscapeCharacter(string $string): string
    {
        return preg_replace('/_(x[\dA-F]{4})_/', '_x005F_$1_', $string);
    }

    /**
     * Converts OpenXML escaped control characters from the given string to PHP control characters.
     *
     * Excel escapes control characters with _xHHHH_ and also escapes any
     * literal strings of that type by encoding the leading underscore.
     * So "_x0000_" -> "\0" and "_x005F_x0000_" -> "_x0000_"
     *
     * NOTE: the logic has been adapted from the XlsxWriter library (BSD License)
     *
     * @see https://github.com/jmcnamara/XlsxWriter/blob/f1e610f29/xlsxwriter/sharedstrings.py#L89
     *
     * @param string $string String to unescape
     */
    private function unescapeControlCharacters(string $string): string
    {
        $unescapedString = $string;

        foreach (self::controlCharactersEscapingMap as $escapedCharValue => $charValue) {
            // only unescape characters that don't contain the escaped escape character for now
            $unescapedString = preg_replace("/(?<!_x005F)({$escapedCharValue})/", $charValue, $unescapedString);
        }

        return $this->unescapeEscapeCharacter($unescapedString);
    }

    /**
     * Unecapes the escape character: "_x005F_x0000_" => "_x0000_".
     *
     * @param string $string String to unescape
     *
     * @return string The unescaped string
     */
    private function unescapeEscapeCharacter(string $string): string
    {
        return preg_replace('/_x005F(_x[\dA-F]{4}_)/', '$1', $string);
    }
}
