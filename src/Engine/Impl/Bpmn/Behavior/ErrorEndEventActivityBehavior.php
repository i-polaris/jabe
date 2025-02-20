<?php

namespace Jabe\Engine\Impl\Bpmn\Behavior;

use Jabe\Engine\Impl\Bpmn\Helper\BpmnExceptionHandler;
use Jabe\Engine\Impl\Core\Variable\Mapping\Value\ParameterValueProviderInterface;
use Jabe\Engine\Impl\Pvm\Delegate\ActivityExecutionInterface;

class ErrorEndEventActivityBehavior extends AbstractBpmnActivityBehavior
{
    protected $errorCode;
    private $errorMessageExpression;

    public function __construct(string $errorCode, ParameterValueProviderInterfce $errorMessage)
    {
        $this->errorCode = $errorCode;
        $this->errorMessageExpression = $errorMessage;
    }

    public function execute(ActivityExecutionInterface $execution): void
    {
        $errorMessageValue = $this->errorMessageExpression != null ? $this->errorMessageExpression->getValue($execution) : null;
        BpmnExceptionHandler::propagateError($this->errorCode, $errorMessageValue, null, $execution);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function setErrorCode(string $errorCode): void
    {
        $this->errorCode = $errorCode;
    }

    public function getErrorMessageExpression(): ParameterValueProviderInterface
    {
        return $this->errorMessageExpression;
    }

    public function setErrorMessageExpression(ParameterValueProviderInterface $errorMessage): void
    {
        $this->errorMessageExpression = $errorMessage;
    }
}
