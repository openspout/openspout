<?php

namespace OpenSpout\Reader\XLSX\Manager;

use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Manager\OptionsManagerInterface;
use OpenSpout\Reader\Common\Entity\Options;
use OpenSpout\Reader\XLSX\Creator\InternalEntityFactory;

/**
 * This class manages the merge cells collection defined in.
 */
class MergeCellsManager
{
    /** Definition of XML nodes names used to parse data */
    public const XML_NODE_MERGE_CELL = 'mergeCell';

    /** Definition of XML attributes used to parse data */
    public const XML_ATTRIBUTE_REF = 'ref';

    /** @var OptionsManagerInterface Reader's options manager */
    protected $optionsManager;

    /** @var InternalEntityFactory Factory to create entities */
    protected $entityFactory;

    /** @var string Path of the XLSX file being read */
    private $filePath;

    /** @var string Path of the sheet data XML file as in [Content_Types].xml */
    private $sheetDataXMLFilePath;

    /** @var null|array Cache of the already read merge cells */
    private $cachedMergeCells;

    public function __construct(
        string $filePath,
        string $sheetDataXMLFilePath,
        OptionsManagerInterface $optionsManager,
        InternalEntityFactory $entityFactory
    ) {
        $this->filePath = $filePath;
        $this->sheetDataXMLFilePath = $sheetDataXMLFilePath;
        $this->optionsManager = $optionsManager;
        $this->entityFactory = $entityFactory;
        $this->cachedMergeCells = null;
    }

    /**
     * Reads the sheet data xml and extracts the merge cells list.
     * It caches the result so that the file is read only once.
     *
     * @return array
     */
    public function getMergeCells()
    {
        if (!isset($this->cachedMergeCells)) {
            $this->cachedMergeCells = [];
            if ($this->optionsManager->getOption(Options::SHOULD_LOAD_MERGE_CELLS)) {
                $xmlReader = $this->entityFactory->createXMLReader();

                if (false === $xmlReader->openFileInZip($this->filePath, $this->sheetDataXMLFilePath)) {
                    throw new IOException('Could not open "'.$this->sheetDataXMLFilePath.'".');
                }

                while ($xmlReader->readUntilNodeFound(self::XML_NODE_MERGE_CELL)) {
                    $this->cachedMergeCells[] = $xmlReader->getAttribute(self::XML_ATTRIBUTE_REF);
                }
            }
        }

        return $this->cachedMergeCells;
    }
}
