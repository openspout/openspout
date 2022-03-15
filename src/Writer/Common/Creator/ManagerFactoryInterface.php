<?php

namespace OpenSpout\Writer\Common\Creator;

use OpenSpout\Common\Manager\OptionsManagerInterface;
use OpenSpout\Writer\Common\Manager\WorkbookManagerInterface;

/**
 * Interface ManagerFactoryInterface.
 */
interface ManagerFactoryInterface
{
    public function createWorkbookManager(OptionsManagerInterface $optionsManager): WorkbookManagerInterface;
}
