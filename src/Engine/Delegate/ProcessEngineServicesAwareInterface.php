<?php

namespace Jabe\Engine\Delegate;

use Jabe\Engine\{
    ProcessEngineInterface,
    ProcessEngineServicesInterface
};

interface ProcessEngineServicesAwareInterface
{
    /**
     * Returns the {@link ProcessEngineServices} providing access to the
     * public API of the process engine.
     *
     * @return the {@link ProcessEngineServices}.
     */
    public function getProcessEngineServices(): ProcessEngineServicesInterface;

    /**
     * Returns the {@link ProcessEngine} providing access to the
     * public API of the process engine.
     *
     * @return the {@link ProcessEngine}.
     */
    public function getProcessEngine(): ProcessEngineInterface;
}
