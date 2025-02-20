<?php

namespace Jabe\Engine\Impl\Cmd\Batch\Variables;

use Jabe\Engine\BadUserRequestException;
use Jabe\Engine\Authorization\BatchPermissions;
use Jabe\Engine\Batch\BatchInterface;
use Jabe\Engine\Batch\History\{
    HistoricBatchInterface,
    HistoricBatchQueryInterface
};
use Jabe\Engine\History\UserOperationLogEntryInterface;
use Jabe\Engine\Impl\Batch\BatchConfiguration;
use Jabe\Engine\Impl\Batch\Builder\{
    BatchBuilder,
    OperationLogInstanceCountHandlerInterface
};
use Jabe\Engine\Impl\Batch\RemovalTime\SetRemovalTimeBatchConfiguration;
use Jabe\Engine\Impl\History\{
    SetRemovalTimeToHistoricBatchesBuilderImpl,
    Mode
};
use Jabe\Engine\Impl\Interceptor\{
    CommandInterface,
    CommandContext
};
use Jabe\Engine\Impl\Persistence\Entity\PropertyChange;
use Jabe\Engine\Impl\Util\EnsureUtil;

class SetRemovalTimeToHistoricBatchesCmd implements CommandInterface
{
    protected $builder;

    public function __construct(SetRemovalTimeToHistoricBatchesBuilderImpl $builder)
    {
        $this->builder = $builder;
    }

    public function execute(CommandContext $commandContext)
    {
        $instanceIds = $this->builder->getIds();
        $instanceQuery = $this->builder->getQuery();
        if ($instanceQuery == null && empty($instanceIds)) {
            throw new BadUserRequestException("Neither query nor ids provided.");
        }

        $collectedInstanceIds = [];
        if ($instanceQuery != null) {
            foreach ($instanceQuery->list() as $historicBatch) {
                $collectedInstanceIds[] = $historicBatch->getId();
            }
        }

        if (!empty($instanceIds)) {
            $collectedInstanceIds = array_merge($collectedInstanceIds, $this->findHistoricInstanceIds($instanceIds, $commandContext));
        }

        EnsureUtil::ensureNotNull(BadUserRequestException::class, "removalTime", $builder->getMode());
        EnsureUtil::ensureNotEmpty(BadUserRequestException::class, "historicBatches", $collectedInstanceIds);

        $scope = $this;
        return (new BatchBuilder($commandContext))
            ->type(BatchInterface::TYPE_BATCH_SET_REMOVAL_TIME)
            ->config($this->getConfiguration($collectedInstanceIds))
            ->permission(BatchPermission::createBatchSetRemovalTime())
            ->operationLogHandler(new class ($scope) implements OperationLogInstanceCountHandlerInterface {
                private $scope;

                public function __construct($scope)
                {
                    $this->scope = $scope;
                }

                public function write(CommandContext $commandContext, int $instanceCount): void
                {
                    $op = $this->scope->writeUserOperationLog;
                    $op($commandContext, $instanceCount);
                }
            })
            ->build();
    }

    protected function findHistoricInstanceIds(array $instanceIds, CommandContext $commandContext): array
    {
        $ids = [];
        foreach ($instanceIds as $instanceId) {
            $batch = $this->createHistoricBatchQuery($commandContext)
            ->batchId($instanceId)
            ->singleResult();

            if ($batch != null) {
                $ids[] = $batch->getId();
            }
        }

        return $ids;
    }

    protected function createHistoricBatchQuery(CommandContext $commandContext): HistoricBatchQueryInterface
    {
        return $commandContext->getProcessEngineConfiguration()
            ->getHistoryService()
            ->createHistoricBatchQuery();
    }

    public function writeUserOperationLog(CommandContext $commandContext, int $numInstances): void
    {
        $propertyChanges = [];
        $propertyChanges[] = new PropertyChange("mode", null, $this->builder->getMode());
        $propertyChanges[] = new PropertyChange("removalTime", null, $this->builder->getRemovalTime());
        $propertyChanges[] = new PropertyChange("nrOfInstances", null, $numInstances);
        $propertyChanges[] = new PropertyChange("async", null, true);

        $commandContext->getOperationLogManager()
            ->logBatchOperation(UserOperationLogEntryInterface::OPERATION_TYPE_SET_REMOVAL_TIME, $propertyChanges);
    }

    protected function hasRemovalTime(): bool
    {
        return $this->builder->getMode() == Mode::ABSOLUTE_REMOVAL_TIME ||
            $this->builder->getMode() == Mode::CLEARED_REMOVAL_TIME;
    }

    public function getConfiguration(array $instances): BatchConfiguration
    {
        return (new SetRemovalTimeBatchConfiguration($instances))
            ->setHasRemovalTime($this->hasRemovalTime())
            ->setRemovalTime($this->builder->getRemovalTime());
    }
}
