<?php

namespace Jabe\Engine\Runtime;

use Jabe\Engine\Batch\BatchInterface;

interface UpdateProcessInstancesSuspensionStateBuilderInterface extends UpdateProcessInstanceSuspensionStateBuilderInterface, UpdateProcessInstancesRequestInterface
{
    /**
     * <p>
     * Activates the provided process instances asynchronously.
     * </p>
     *
     * <p>
     * If you have a process instance hierarchy, activating one process instance
     * from the hierarchy will not activate other process instances from that
     * hierarchy.
     * </p>
     *
     * @throws org.camunda.bpm.engine.BadUserRequestException
     *           If no process Instances are found
     *           If a process Instance is set to null
     * @throws AuthorizationException
     *           if the user has no {@link Permissions#CREATE} or
     *           {@link BatchPermissions#CREATE_BATCH_UPDATE_PROCESS_INSTANCES_SUSPEND} permission
     *           on {@link Resources#BATCH}.
     */
    public function activateAsync(): BatchInterface;

    /**
     * <p>
     * Suspends the provided process instances asynchronously. This means that the execution is
     * stopped, so the <i>token state</i> will not change. However, actions that
     * do not change token state, like setting/removing variables, etc. will
     * succeed.
     * </p>
     *
     * <p>
     * Tasks belonging to the suspended process instance will also be suspended.
     * This means that any actions influencing the tasks' lifecycles will fail,
     * such as
     * <ul>
     * <li>claiming</li>
     * <li>completing</li>
     * <li>delegation</li>
     * <li>changes in task assignees, owners, etc.</li>
     * </ul>
     * Actions that only change task properties will succeed, such as changing
     * variables or adding comments.
     * </p>
     *
     * <p>
     * If a process instance is in state suspended, the engine will also not
     * execute jobs (timers, messages) associated with this instance.
     * </p>
     *
     * <p>
     * If you have a process instance hierarchy, suspending one process instance
     * from the hierarchy will not suspend other process instances from that
     * hierarchy.
     * </p>
     *
     * @throws org.camunda.bpm.engine.BadUserRequestException
     *           If no process Instances are found
     *           If a process Instance is set to null
     * @throws AuthorizationException
     *           if the user has no {@link Permissions#CREATE} or
     *           {@link BatchPermissions#CREATE_BATCH_UPDATE_PROCESS_INSTANCES_SUSPEND} permission
     *           on {@link Resources#BATCH}.
     */
    public function suspendAsync(): BatchInterface;
}
