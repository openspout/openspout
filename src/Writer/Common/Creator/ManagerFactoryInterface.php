<?php

namespace OpenSpout\Writer\Common\Creator;

use OpenSpout\Common\Manager\OptionsManagerInterface;
use OpenSpout\Writer\Common\Manager\WorkbookManagerInterface;

/**
 * @template O of OptionsManagerInterface
 * @template W of WorkbookManagerInterface
 */
interface ManagerFactoryInterface
{
    /**
     * @param O $optionsManager
     *
     * @return W
     */
    public function createWorkbookManager(OptionsManagerInterface $optionsManager): WorkbookManagerInterface;
}
