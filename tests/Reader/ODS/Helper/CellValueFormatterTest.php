<?php

declare(strict_types=1);

namespace OpenSpout\Reader\ODS\Helper;

use DOMDocument;
use DOMElement;
use OpenSpout\Common\Helper\Escaper\ODS;
use OpenSpout\Reader\Exception\InvalidValueException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CellValueFormatterTest extends TestCase
{
    /**
     * @throws InvalidValueException
     */
    #[DataProvider('provideBooleanCases')]
    public function testBoolean(?string $booleanValue, bool $expectedValue): void
    {
        $formatter = new CellValueFormatter(false, new ODS());

        self::assertSame($expectedValue, $formatter->extractAndFormatNodeValue($this->getBooleanNode($booleanValue)));
    }

    public static function provideBooleanCases(): iterable
    {
        return [
            // "office:boolean-value" holds an xsd:boolean, which has four lexical forms
            'true' => ['true', true],
            'false' => ['false', false],
            'one' => ['1', true],
            'zero' => ['0', false],

            'empty' => ['', false],
            'missing attribute' => [null, false],
        ];
    }

    /**
     * @param null|string $booleanValue Value of the "office:boolean-value" attribute, null to omit the attribute
     */
    private function getBooleanNode(?string $booleanValue): DOMElement
    {
        $booleanAttribute = null === $booleanValue ? '' : " office:boolean-value=\"{$booleanValue}\"";

        $document = new DOMDocument();
        self::assertTrue($document->loadXML(
            '<table:table-cell xmlns:table="urn:table" xmlns:office="urn:office"'
            .' office:value-type="boolean"'.$booleanAttribute.'/>'
        ));

        $node = $document->documentElement;
        self::assertInstanceOf(DOMElement::class, $node);

        return $node;
    }
}
