<?php

namespace Jabe\Engine\Impl\JobExecutor;

use Jabe\Engine\ProcessEngineException;
use Jabe\Engine\Impl\Interceptor\CommandContext;
use Jabe\Engine\Impl\Persistence\Entity\{
    ExecutionEntity,
    TaskEntity
};

class TimerTaskListenerJobHandler extends TimerEventJobHandler
{
    public const TYPE = "timer-task-listener";

    public function getType(): string
    {
        return self::TYPE;
    }

    public function execute(TimerJobConfiguration $configuration, ExecutionEntity $execution, CommandContext $commandContext, ?string $tenantId): void
    {
        $activityId = $configuration->getTimerElementKey();
        $targetTask = null;
        foreach ($execution->getTasks() as $task) {
            if ($task->getTaskDefinitionKey() == $activityId) {
                $targetTask = $task;
            }
        }

        if ($targetTask != null) {
            $targetTask->triggerTimeoutEvent($configuration->getTimerElementSecondaryKey());
        } else {
            throw new ProcessEngineException("Error while triggering timeout task listener '" . $configuration->getTimerElementSecondaryKey()
                . "': cannot find task for activity id '" . $configuration->getTimerElementKey() . "'.");
        }
    }
}
