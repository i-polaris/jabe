<?php

namespace Jabe\Model\Bpmn\Builder;

use Jabe\Model\Bpmn\BpmnModelInstanceInterface;
use Jabe\Model\Bpmn\Instance\TaskInterface;

class TaskBuilder extends AbstractTaskBuilder
{
    public function __construct(
        BpmnModelInstanceInterface $modelInstance,
        TaskInterface $element
    ) {
        parent::__construct($modelInstance, $element, TaskBuilder::class);
    }
}
