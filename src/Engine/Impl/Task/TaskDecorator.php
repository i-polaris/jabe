<?php

namespace Jabe\Engine\Impl\Task;

use Jabe\Engine\ProcessEngineException;
use Jabe\Engine\Delegate\{
    ExpressionInterface,
    VariableScopeInterface
};
use Jabe\Engine\Impl\Calendar\{
    BusinessCalendarInterface,
    DueDateBusinessCalendar
};
use Jabe\Engine\Impl\Context\Context;
use Jabe\Engine\Impl\El\ExpressionManager;
use Jabe\Engine\Impl\Persistence\Entity\TaskEntity;

class TaskDecorator
{
    protected $taskDefinition;
    protected $expressionManager;

    public function __construct(TaskDefinition $taskDefinition, ExpressionManager $expressionManager)
    {
        $this->taskDefinition = $taskDefinition;
        $this->expressionManager = $expressionManager;
    }

    public function decorate(TaskEntity $task, VariableScopeInterface $variableScope): void
    {
        // set the taskDefinition
        $task->setTaskDefinition($taskDefinition);
        // name
        $this->initializeTaskName($task, $variableScope);
        // description
        $this->initializeTaskDescription($task, $variableScope);
        // dueDate
        $this->initializeTaskDueDate($task, $variableScope);
        // followUpDate
        $this->initializeTaskFollowUpDate($task, $variableScope);
        // priority
        $this->initializeTaskPriority($task, $variableScope);
        // assignments
        $this->initializeTaskAssignments($task, $variableScope);
    }

    protected function initializeTaskName(TaskEntity $task, VariableScopeInterface $variableScope): void
    {
        $nameExpression = $taskDefinition->getNameExpression();
        if ($nameExpression != null) {
            $name = $nameExpression->getValue($variableScope);
            $task->setName($name);
        }
    }

    protected function initializeTaskDescription(TaskEntity $task, VariableScopeInterface $variableScope): void
    {
        $descriptionExpression = $taskDefinition->getDescriptionExpression();
        if ($descriptionExpression != null) {
            $description = $descriptionExpression->getValue($variableScope);
            $task->setDescription($description);
        }
    }

    protected function initializeTaskDueDate(TaskEntity $task, VariableScopeInterface $variableScope): void
    {
        $dueDateExpression = $taskDefinition->getDueDateExpression();
        if ($dueDateExpression != null) {
            $dueDate = $dueDateExpression->getValue($variableScope);
            if ($dueDate != null) {
                if ($dueDate instanceof \DateTime) {
                    $task->setDueDate($dueDate->format('c'));
                } elseif (is_string($dueDate)) {
                    $businessCalendar = $this->getBusinessCalender();
                    $task->setDueDate($businessCalendar->resolveDuedate($dueDate, $task)->format('c'));
                } else {
                    throw new ProcessEngineException("Due date expression does not resolve to a Date or Date string: " .
                        $dueDateExpression->getExpressionText());
                }
            }
        }
    }

    protected function initializeTaskFollowUpDate(TaskEntity $task, VariableScopeInterface $variableScope): void
    {
        $followUpDateExpression = $taskDefinition->getFollowUpDateExpression();
        if ($followUpDateExpression != null) {
            $followUpDate = $followUpDateExpression->getValue($variableScope);
            if ($followUpDate != null) {
                if ($followUpDate instanceof \DateTime) {
                    $task->setFollowUpDate($followUpDate->format('c'));
                } elseif (is_string($followUpDate)) {
                    $businessCalendar = $this->getBusinessCalender();
                    $task->setFollowUpDate($businessCalendar->resolveDuedate($followUpDate, $task)->format('c'));
                } else {
                    throw new ProcessEngineException("Follow up date expression does not resolve to a Date or Date string: " .
                        $followUpDateExpression->getExpressionText());
                }
            }
        }
    }

    protected function initializeTaskPriority(TaskEntity $task, VariableScopeInterface $variableScope): void
    {
        $priorityExpression = $taskDefinition->getPriorityExpression();
        if ($priorityExpression != null) {
            $priority = $priorityExpression->getValue($variableScope);
            if ($priority != null) {
                if (is_string($priority)) {
                    try {
                        $task->setPriority(intval($priority));
                    } catch (\Exception $e) {
                        throw new ProcessEngineException("Priority does not resolve to a number: " . $priority, $e);
                    }
                } elseif (is_numeric($priority)) {
                    $task->setPriority(intval($priority));
                } else {
                    throw new ProcessEngineException("Priority expression does not resolve to a number: " .
                            $priorityExpression->getExpressionText());
                }
            }
        }
    }

    protected function initializeTaskAssignments(TaskEntity $task, VariableScopeInterface $variableScope): void
    {
        // assignee
        $this->initializeTaskAssignee($task, $variableScope);
        // candidateUsers
        $this->initializeTaskCandidateUsers($task, $variableScope);
        // candidateGroups
        $this->initializeTaskCandidateGroups($task, $variableScope);
    }

    protected function initializeTaskAssignee(TaskEntity $task, VariableScopeInterface $variableScope): void
    {
        $assigneeExpression = $taskDefinition->getAssigneeExpression();
        if ($assigneeExpression != null) {
            $task->setAssignee($assigneeExpression->getValue($variableScope));
        }
    }

    protected function initializeTaskCandidateGroups(TaskEntity $task, VariableScopeInterface $variableScope): void
    {
        $candidateGroupIdExpressions = $taskDefinition->getCandidateGroupIdExpressions();

        foreach ($candidateGroupIdExpressions as $groupIdExpr) {
            $value = $groupIdExpr->getValue($variableScope);

            if (is_string($value)) {
                $candiates = $this->extractCandidates($value);
                $task->addCandidateGroups($candiates);
            } elseif (is_array($value)) {
                $task->addCandidateGroups($value);
            } else {
                throw new ProcessEngineException("Expression did not resolve to a string or collection of strings");
            }
        }
    }

    protected function initializeTaskCandidateUsers(TaskEntity $task, VariableScopeInterface $variableScope): void
    {
        $candidateUserIdExpressions = $taskDefinition->getCandidateUserIdExpressions();
        foreach ($candidateUserIdExpressions as $userIdExpr) {
            $value = $userIdExpr->getValue($variableScope);
            if (is_string($value)) {
                $candiates = $this->extractCandidates($value);
                $task->addCandidateUsers($candiates);
            } elseif (is_array($value)) {
                $task->addCandidateUsers($value);
            } else {
                throw new ProcessEngineException("Expression did not resolve to a string or collection of strings");
            }
        }
    }

    /**
     * Extract a candidate list from a string.
     */
    protected function extractCandidates(string $str): array
    {
        return preg_split("/[\s]*,[\s]*/", $str);
    }

    // getters ///////////////////////////////////////////////////////////////

    public function getTaskDefinition(): TaskDefinition
    {
        return $this->taskDefinition;
    }

    public function getExpressionManager(): ExpressionManager
    {
        return $this->expressionManager;
    }

    protected function getBusinessCalender(): BusinessCalendarInterface
    {
        return Context::getProcessEngineConfiguration()
            ->getBusinessCalendarManager()
            ->getBusinessCalendar(DueDateBusinessCalendar::NAME);
    }
}
