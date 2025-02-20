<?php

namespace Jabe\Engine\Impl\Form\Validator;

use Jabe\Engine\Delegate\{
    DelegateExecutionInterface,
    VariableScopeInterface
};
use Jabe\Engine\Impl\Form\Handler\FormFieldHandler;

interface FormFieldValidatorContextInterface
{
    public function getFormFieldHandler(): FormFieldHandler;

    /** @return the execution
     * Deprecated, use {@link #getVariableScope()} */
    public function getExecution(): DelegateExecutionInterface;

    /**
     * @return the variable scope in which the value is submitted
     */
    public function getVariableScope(): VariableScopeInterface;

    /** @return the configuration of this validator */
    public function getConfiguration(): string;

    /** @return all values submitted in the form */
    public function getSubmittedValues(): array;
}
