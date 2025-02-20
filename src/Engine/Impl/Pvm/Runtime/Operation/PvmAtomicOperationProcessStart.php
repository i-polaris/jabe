<?php

namespace Jabe\Engine\Impl\Pvm\Runtime\Operation;

use Jabe\Engine\Delegate\ExecutionListenerInterface;
use Jabe\Engine\Impl\Core\Model\CoreModelElement;
use Jabe\Engine\Impl\Core\Instance\CoreExecution;
use Jabe\Engine\Impl\Pvm\Runtime\{
    CallbackInterface,
    LegacyBehavior,
    PvmExecutionImpl
};

class PvmAtomicOperationProcessStart extends AbstractPvmEventAtomicOperation
{
    public function isAsync(PvmExecutionImpl $execution): bool
    {
        return $execution->getActivity()->isAsyncBefore();
    }

    public function isAsyncCapable(): bool
    {
        return true;
    }

    protected function getScope(CoreExecution $execution): CoreModelElement
    {
        return $execution->getProcessDefinition();
    }

    protected function getEventName(): string
    {
        return ExecutionListenerInterface::EVENTNAME_START;
    }

    protected function eventNotificationsStarted(CoreExecution $execution): CoreExecution
    {
        // restoring the starting flag in case this operation is executed
        // asynchronously
        $execution->setProcessInstanceStarting(true);

        if ($execution->getActivity() != null && $execution->getActivity()->isAsyncBefore()) {
            LegacyBehavior::createMissingHistoricVariables($execution);
        }

        return $execution;
    }

    protected function eventNotificationsCompleted(CoreExecution $execution): void
    {
        $execution->continueIfExecutionDoesNotAffectNextOperation(new class () implements CallbackInterface {
            public function callback($execution)
            {
                $execution->dispatchEvent(null);
                return null;
            }
        }, new class () implements CallbackInterface {
            public function callback($execution)
            {
                $execution->setIgnoreAsync(true);
                $execution->performOperation(PvmAtomicOperationProcessStart::activityStartCreateScope());
                return null;
            }
        }, $execution);
    }

    public function getCanonicalName(): string
    {
        return "process-start";
    }
}
