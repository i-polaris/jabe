<?php

namespace Jabe\Engine\Impl\History;

use Jabe\Engine\Impl\Batch\History\HistoricBatchEntity;
use Jabe\Engine\Impl\History\Event\{
    HistoricProcessInstanceEventEntity
};
use Jabe\Engine\Repository\{
    ProcessDefinitionInterface
};

interface HistoryRemovalTimeProviderInterface
{
    /**
     * Calculates the removal time of historic entities or batches.
     *
     * @param mixed $historicRootInstance
     * @param mixed $definition
     *
     * @return the removal time for historic process instances
     */
    public function calculateRemovalTime($instance, $definition = null): string;
}
