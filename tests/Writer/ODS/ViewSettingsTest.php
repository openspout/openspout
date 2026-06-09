<?php

declare(strict_types=1);

namespace Writer\ODS;

use DOMElement;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\Wrapper\XMLReader;
use OpenSpout\TestUsingResource;
use OpenSpout\Writer\ODS\Entity\ViewSettings;
use OpenSpout\Writer\ODS\Writer;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ViewSettingsTest extends TestCase
{
    public function testSheetViewSettingsShouldUseDefaultValuesWhenNotExplicitlySet(): void
    {
        $fileName = 'test_default_view_settings.ods';

        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $writer = new Writer();
        $writer->openToFile($resourcePath);
        $writer->addRow(Row::fromValues(['ods--sheet1--11', 'ods--sheet1--12']));
        $sheet = $writer->getCurrentSheet();

        /** @var non-empty-string $sheetName */
        $sheetName = $sheet->getName();
        $sheet->setSheetView(new ViewSettings($sheetName));
        $writer->close();

        $this->assertConfigItem($fileName, 'VerticalSplitPosition', '0', 'Default VerticalSplitPosition should be 0');
        $this->assertConfigItem($fileName, 'HorizontalSplitPosition', '0', 'Default HorizontalSplitPosition should be 0');
        $this->assertConfigItem($fileName, 'ShowGrid', 'true', 'Default ShowGrid should be true');
    }

    public function testSheetViewSettingsShouldBePersistedToSettingsXml(): void
    {
        $fileName = 'test_explicit_view_settings.ods';

        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $writer = new Writer();
        $writer->openToFile($resourcePath);
        $writer->addRow(Row::fromValues(['ods--sheet1--11', 'ods--sheet1--12']));
        $sheet = $writer->getCurrentSheet();

        /** @var non-empty-string $sheetName */
        $sheetName = $sheet->getName();
        $sheet->setSheetView(new ViewSettings($sheetName, 1, 2, false));
        $writer->close();

        $this->assertConfigItem($fileName, 'VerticalSplitPosition', '1', 'VerticalSplitPosition should be persisted from ViewSettings');
        $this->assertConfigItem($fileName, 'HorizontalSplitPosition', '2', 'HorizontalSplitPosition should be persisted from ViewSettings');
        $this->assertConfigItem($fileName, 'ShowGrid', 'false', 'ShowGrid should be persisted from ViewSettings');
    }

    private function assertConfigItem(string $fileName, string $configName, string $expected, string $message): void
    {
        self::assertEquals(
            $expected,
            $this->getConfigItemValueFromSettingsXmlFile($fileName, $configName),
            $message
        );
    }

    private function getConfigItemValueFromSettingsXmlFile(string $fileName, string $configName): ?string
    {
        $value = null;

        $resourcePath = (new TestUsingResource())->getGeneratedResourcePath($fileName);

        $xmlReader = new XMLReader();
        $xmlReader->openFileInZip($resourcePath, 'settings.xml');

        while ($xmlReader->read()) {
            if ($xmlReader->isPositionedOnStartingNode('config:config-item') && $configName === $xmlReader->getAttribute('config:name')) {
                /** @var DOMElement $element */
                $element = $xmlReader->expand();
                $value = $element->nodeValue;

                break;
            }
        }

        $xmlReader->close();

        return $value;
    }
}
