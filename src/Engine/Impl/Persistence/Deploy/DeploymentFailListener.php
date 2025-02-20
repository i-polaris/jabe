<?php

namespace Jabe\Engine\Impl\Persistence\Deploy;

use Jabe\Engine\Impl\Cfg\TransactionListenerInterface;
use Jabe\Engine\Impl\Interceptor\{
    CommandContext,
    CommandExecutorInterface
};

class DeploymentFailListener implements TransactionListenerInterface
{
    protected $deploymentIds;
    protected $processApplicationReference;
    protected $commandExecutor;

    public function __construct($deploymentId, CommandExecutorInterface $commandExecutor)
    {
        if (is_array($deploymentId)) {
            $this->deploymentIds = $deploymentId;
        } else {
            $this->deploymentIds = [$deploymentId];
        }
        $this->commandExecutor = $commandExecutor;
    }

    public function execute(CommandContext $commandContext): void
    {
        //we can not use commandContext parameter here, as it can be in inconsistent state
        $this->commandExecutor->execute(new DeleteDeploymentListenerCmd($this->deploymentId, $this->processApplicationReference));
    }
}
