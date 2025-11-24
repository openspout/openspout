<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Manager\Style;

use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\Common\Manager\Style\AbstractStyleRegistry as CommonStyleRegistry;

/**
 * @internal
 */
class StyleRegistry extends CommonStyleRegistry
{
    /**
     * Mapping between built-in format and the associated numFmtId.
     *
     * @see https://msdn.microsoft.com/en-us/library/ff529597(v=office.12).aspx
     */
    private const builtinNumFormatToIdMapping = [
        'General' => 0,
        '0' => 1,
        '0.00' => 2,
        '#,##0' => 3,
        '#,##0.00' => 4,
        '$#,##0,\-$#,##0' => 5,
        '$#,##0,[Red]\-$#,##0' => 6,
        '$#,##0.00,\-$#,##0.00' => 7,
        '$#,##0.00,[Red]\-$#,##0.00' => 8,
        '0%' => 9,
        '0.00%' => 10,
        '0.00E+00' => 11,
        '# ?/?' => 12,
        '# ??/??' => 13,
        'mm-dd-yy' => 14,
        'd-mmm-yy' => 15,
        'd-mmm' => 16,
        'mmm-yy' => 17,
        'h:mm AM/PM' => 18,
        'h:mm:ss AM/PM' => 19,
        'h:mm' => 20,
        'h:mm:ss' => 21,
        'm/d/yy h:mm' => 22,

        '#,##0 ,(#,##0)' => 37,
        '#,##0 ,[Red](#,##0)' => 38,
        '#,##0.00,(#,##0.00)' => 39,
        '#,##0.00,[Red](#,##0.00)' => 40,

        '_("$"* #,##0.00_),_("$"* \(#,##0.00\),_("$"* "-"??_),_(@_)' => 44,
        'mm:ss' => 45,
        '[h]:mm:ss' => 46,
        'mm:ss.0' => 47,

        '##0.0E+0' => 48,
        '@' => 49,

        '[$-404]e/m/d' => 27,
        'm/d/yy' => 30,
        't0' => 59,
        't0.00' => 60,
        't#,##0' => 61,
        't#,##0.00' => 62,
        't0%' => 67,
        't0.00%' => 68,
        't# ?/?' => 69,
        't# ??/??' => 70,
    ];

    /** @var array<string, int> */
    private array $registeredFormats = [];

    /** @var array<int, int> [STYLE_ID] => [FORMAT_ID] maps a style to a format declaration */
    private array $styleIdToFormatsMappingTable = [];

    /**
     * If the numFmtId is lower than 0xA4 (164 in decimal)
     * then it's a built-in number format.
     * Since Excel is the dominant vendor - we play along here.
     *
     * @var int the fill index counter for custom fills
     */
    private int $formatIndex = 164;

    /** @var array<string, int> */
    private array $registeredFills = [];

    /** @var array<int, int> [STYLE_ID] => [FILL_ID] maps a style to a fill declaration */
    private array $styleIdToFillMappingTable = [];

    /**
     * Excel preserves two default fills with index 0 and 1
     * Since Excel is the dominant vendor - we play along here.
     *
     * @var int the fill index counter for custom fills
     */
    private int $fillIndex = 2;

    /** @var array<string, int> */
    private array $registeredBorders = [];

    /** @var array<int, int> [STYLE_ID] => [BORDER_ID] maps a style to a border declaration */
    private array $styleIdToBorderMappingTable = [];

    /**
     * @return null|int Format ID associated to the given style ID
     */
    public function getFormatIdForStyleId(int $styleId): ?int
    {
        return $this->styleIdToFormatsMappingTable[$styleId] ?? null;
    }

    /**
     * @return null|int Fill ID associated to the given style ID
     */
    public function getFillIdForStyleId(int $styleId): ?int
    {
        return $this->styleIdToFillMappingTable[$styleId] ?? null;
    }

    /**
     * @return null|int Fill ID associated to the given style ID
     */
    public function getBorderIdForStyleId(int $styleId): ?int
    {
        return $this->styleIdToBorderMappingTable[$styleId] ?? null;
    }

    /**
     * @return array<string, int>
     */
    public function getRegisteredFills(): array
    {
        return $this->registeredFills;
    }

    /**
     * @return array<string, int>
     */
    public function getRegisteredBorders(): array
    {
        return $this->registeredBorders;
    }

    /**
     * @return array<string, int>
     */
    public function getRegisteredFormats(): array
    {
        return $this->registeredFormats;
    }

    /**
     * XLSX specific operations on the registered styles.
     */
    protected function customRegisterStyle(int $styleId, Style $style): void
    {
        $this->registerFill($styleId, $style);
        $this->registerFormat($styleId, $style);
        $this->registerBorder($styleId, $style);
    }

    private function registerFormat(int $styleId, Style $style): void
    {
        $format = $style->format;
        if (null === $format) {
            // The formatId maps a style to a format declaration
            // When there is no format definition - we default to 0 ( General )
            $this->styleIdToFormatsMappingTable[$styleId] = 0;

            return;
        }

        $isFormatRegistered = isset($this->registeredFormats[$format]);

        // We need to track the already registered format definitions
        if ($isFormatRegistered) {
            $registeredStyleId = $this->registeredFormats[$format];
            $registeredFormatId = $this->styleIdToFormatsMappingTable[$registeredStyleId];
            $this->styleIdToFormatsMappingTable[$styleId] = $registeredFormatId;
        } else {
            $this->registeredFormats[$format] = $styleId;

            $id = self::builtinNumFormatToIdMapping[$format] ?? $this->formatIndex++;
            $this->styleIdToFormatsMappingTable[$styleId] = $id;
        }
    }

    private function registerFill(int $styleId, Style $style): void
    {
        // Currently - only solid backgrounds are supported
        // so $backgroundColor is a scalar value (RGB Color)
        $backgroundColor = $style->backgroundColor;

        if (null === $backgroundColor) {
            // The fillId maps a style to a fill declaration
            // When there is no background color definition - we default to 0
            $this->styleIdToFillMappingTable[$styleId] = 0;

            return;
        }

        $isBackgroundColorRegistered = isset($this->registeredFills[$backgroundColor]);

        // We need to track the already registered background definitions
        if ($isBackgroundColorRegistered) {
            $registeredStyleId = $this->registeredFills[$backgroundColor];
            $registeredFillId = $this->styleIdToFillMappingTable[$registeredStyleId];
            $this->styleIdToFillMappingTable[$styleId] = $registeredFillId;
        } else {
            $this->registeredFills[$backgroundColor] = $styleId;
            $this->styleIdToFillMappingTable[$styleId] = $this->fillIndex++;
        }
    }

    private function registerBorder(int $styleId, Style $style): void
    {
        if (null === ($border = $style->border)) {
            // If no border should be applied - the mapping is the default border: 0
            $this->styleIdToBorderMappingTable[$styleId] = 0;

            return;
        }

        $serializedBorder = serialize($border);
        $isBorderAlreadyRegistered = isset($this->registeredBorders[$serializedBorder]);

        if ($isBorderAlreadyRegistered) {
            $registeredStyleId = $this->registeredBorders[$serializedBorder];
            $registeredBorderId = $this->styleIdToBorderMappingTable[$registeredStyleId];
            $this->styleIdToBorderMappingTable[$styleId] = $registeredBorderId;
        } else {
            $this->registeredBorders[$serializedBorder] = $styleId;
            $this->styleIdToBorderMappingTable[$styleId] = \count($this->registeredBorders);
        }
    }
}
