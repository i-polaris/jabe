<?php

namespace Jabe\Engine\Impl\Interceptor;

use Jabe\Engine\Application\{
    InvocationContext,
    ProcessApplicationReferenceInterface
};
use Jabe\Engine\ProcessEngineException;
use Jabe\Engine\Impl\ProcessEngineLogger;
use Jabe\Engine\Impl\Cfg\ProcessEngineConfigurationImpl;
use Jabe\Engine\Impl\Cmd\CommandLogger;
use Jabe\Engine\Impl\Context\{
    Context,
    ProcessApplicationContextUtil
};
use Jabe\Engine\Impl\Persistence\Entity\ExecutionEntity;
use Jabe\Engine\Impl\Pvm\Runtime\AtomicOperation;

class CommandInvocationContext
{
    //private final static CommandLogger LOG = ProcessEngineLogger.CMD_LOGGER;

    protected $throwable;
    protected $command;
    protected $isExecuting = false;
    protected $queuedInvocations = [];
    protected $bpmnStackTrace;
    protected $processDataContext;

    public function __construct(CommandInterface $command, ProcessEngineConfigurationImpl $configuration)
    {
        $this->bpmnStackTrace = new BpmnStackTrace();
        $this->command = $command;
        $this->processDataContext = new ProcessDataContext($configuration);
    }

    public function getThrowable(): ?\Throwable
    {
        return $this->throwable;
    }

    public function getCommand(): CommandInterface
    {
        return $this->command;
    }

    public function trySetThrowable(\Throwable $t): void
    {
        if ($this->throwable == null) {
            $this->throwable = $t;
        } else {
            //LOG.maskedExceptionInCommandContext(throwable);
        }
    }

    public function performOperationAsync(AtomicOperation $executionOperation, ExecutionEntity $execution): void
    {
        $this->performOperation($executionOperation, $execution, true);
    }

    public function performOperation(AtomicOperation $executionOperation, ExecutionEntity $execution, ?bool $performAsync = false): void
    {
        $invocation = new AtomicOperationInvocation($executionOperation, $execution, $performAsync);
        array_unsift($this->queuedInvocations, $invocation);
        $this->performNext();
    }

    public function performNext(): void
    {
        $nextInvocation = $this->queuedInvocations[0];

        if ($nextInvocation->operation->isAsyncCapable() && $this->isExecuting) {
            // will be picked up by while loop below
            return;
        }

        $targetProcessApplication = $this->getTargetProcessApplication($nextInvocation->execution);
        if ($this->requiresContextSwitch($targetProcessApplication)) {
            $scope = $this;
            Context::executeWithinProcessApplication(function () use ($scope) {
                $scope->performNext();
                return null;
            }, $targetProcessApplication, new InvocationContext($nextInvocation->execution));
        } else {
            if (!$nextInvocation->operation->isAsyncCapable()) {
                // if operation is not async capable, perform right away.
                $this->invokeNext();
            } else {
                try {
                    $this->isExecuting = true;
                    while (!empty($this->queuedInvocations)) {
                        // assumption: all operations are executed within the same process application...
                        $this->invokeNext();
                    }
                } finally {
                    $this->isExecuting = false;
                }
            }
        }
    }

    protected function invokeNext(): void
    {
        $invocation = array_shift($this->queuedInvocations);
        try {
            if ($invocation != null) {
                $invocation->execute($this->bpmnStackTrace, processDataContext);
            }
        } catch (\Exception $e) {
            // log bpmn stacktrace
            $this->bpmnStackTrace->printStackTrace(Context::getProcessEngineConfiguration()->isBpmnStacktraceVerbose());
            // rethrow
            throw $e;
        }
    }

    protected function requiresContextSwitch(ProcessApplicationReferenceInterface $processApplicationReference): bool
    {
        return ProcessApplicationContextUtil::requiresContextSwitch($processApplicationReference);
    }

    protected function getTargetProcessApplication(ExecutionEntity $execution): ?ProcessApplicationReferenceInterface
    {
        return ProcessApplicationContextUtil::getTargetProcessApplication($execution);
    }

    public function rethrow(): void
    {
        if ($this->throwable != null) {
            throw new ProcessEngineException("exception while executing command ", $throwable);
        }
    }

    public function getProcessDataContext(): ProcessDataContext
    {
        return $this->processDataContext;
    }
}
