<?php

namespace Jabe\Engine;

use Jabe\Model\Bpmn\BpmnModelInstanceInterface;
use Jabe\Engine\Application\ProcessApplicationReferenceInterface;
use Jabe\Engine\Authorization\{
    Permissions,
    ProcessDefinitionPermissions,
    ProcessInstancePermissions,
    Resources
};
use Jabe\Engine\Delegate\ExecutionListenerInterface;
use Jabe\Engine\Exception\{
    NotFoundException,
    NotValidException
};
use Jabe\Engine\Repository\{
    DeleteProcessDefinitionsSelectBuilderInterface,
    DeploymentBuilderInterface,
    DeploymentQueryInterface,
    DiagramLayout,
    ProcessApplicationDeploymentInterface,
    ProcessApplicationDeploymentBuilderInterface,
    ProcessDefinitionInterface,
    ProcessDefinitionQueryInterface,
    ResourceInterface,
    UpdateProcessDefinitionSuspensionStateBuilderInterface,
    UpdateProcessDefinitionSuspensionStateSelectBuilderInterface
};
use Jabe\Engine\Task\IdentityLinkInterface;

interface RepositoryServiceInterface
{
    /**
     * Starts creating a new {@link ProcessApplicationDeployment}.
     *
     * @see ProcessApplicationDeploymentBuilder
     */
    public function createDeployment(?ProcessApplicationReference $processApplication = null): ProcessApplicationDeploymentBuilderInterface;

    /**
     * Deletes the given deployment.
     *
     * @param deploymentId id of the deployment, cannot be null.
     *
     * @throws RuntimeException
     *          If there are still runtime or history process instances or jobs.
     * @throws AuthorizationException
     *          If the user has no {@link Permissions#DELETE} permission on {@link Resources#DEPLOYMENT}.
     */
    public function deleteDeployment(string $deploymentId, ?bool $cascade = false, ?bool $skipCustomListeners = false, bool $skipIoMappings = false): void;

    /**
     * Deletes the process definition which belongs to the given process definition id.
     * Cascades the deletion if the cascade is set to true, the custom listener can be skipped if
     * the third parameter is set to true, io mappings can be skipped if the forth parameter is set to true.
     *
     * @param processDefinitionId the id, which corresponds to the process definition
     * @param cascade if set to true, all process instances (including) history are deleted
     * @param skipCustomListeners if true, only the built-in {@link ExecutionListener}s
     *            are notified with the {@link ExecutionListener#EVENTNAME_END} event.
     *            Is only used if cascade set to true.
     * @param skipIoMappings Specifies whether input/output mappings for tasks should be invoked
     *
     * @throws ProcessEngineException
     *          If the process definition does not exist
     *
     * @throws AuthorizationException
     *          If the user has no {@link Permissions#DELETE} permission on {@link Resources#PROCESS_DEFINITION}.
     */
    public function deleteProcessDefinition(string $processDefinitionId, ?bool $cascade = false, ?bool $skipCustomListeners = false, ?bool $skipIoMappings = false): void;

    /**
     * Fluent builder to delete process definitions.
     *
     * @return the builder to delete process definitions
     */
    public function deleteProcessDefinitions(): DeleteProcessDefinitionsSelectBuilderInterface;

    /**
     * Retrieves a list of deployment resource names for the given deployment,
     * ordered alphabetically.
     *
     * @param deploymentId id of the deployment, cannot be null.
     *
     * @throws AuthorizationException
     *          If the user has no {@link Permissions#READ} permission on {@link Resources#DEPLOYMENT}.
     */
    public function getDeploymentResourceNames(string $deploymentId): array;

    /**
     * Retrieves a list of deployment resources for the given deployment,
     * ordered alphabetically by name.
     *
     * @param deploymentId id of the deployment, cannot be null.
     *
     * @throws AuthorizationException
     *          If the user has no {@link Permissions#READ} permission on {@link Resources#DEPLOYMENT}.
     */
    public function getDeploymentResources(string $deploymentId): array;

    /**
     * Gives access to a deployment resource through a stream of bytes.
     *
     * @param deploymentId id of the deployment, cannot be null.
     * @param resourceName name of the resource, cannot be null.
     *
     * @throws ProcessEngineException
     *          When the resource doesn't exist in the given deployment or when no deployment exists
     *          for the given deploymentId.
     * @throws AuthorizationException
     *          If the user has no {@link Permissions#READ} permission on {@link Resources#DEPLOYMENT}.
     */
    public function getResourceAsStream(string $deploymentId, string $resourceName);

    /**
     * Gives access to a deployment resource through a stream of bytes.
     *
     * @param deploymentId id of the deployment, cannot be null.
     * @param resourceId id of the resource, cannot be null.
     *
     * @throws ProcessEngineException
     *          When the resource doesn't exist in the given deployment or when no deployment exists
     *          for the given deploymentId.
     * @throws AuthorizationException
     *          If the user has no {@link Permissions#READ} permission on {@link Resources#DEPLOYMENT}.
     */
    public function getResourceAsStreamById(string $deploymentId, string $resourceId);

    /**
     * Query process definitions.
     */
    public function createProcessDefinitionQuery(): ProcessDefinitionQueryInterface;

    /**
     * Query process definitions.
     */
    public function createDeploymentQuery(): DeploymentQueryInterface;

    /**
     * Suspends the process definition with the given id.
     *
     * If a process definition is in state suspended, it will not be possible to start new process instances
     * based on the process definition.
     *
     * <p>Note: for more complex suspend commands use {@link #updateProcessDefinitionSuspensionState()}.</p>
     *
     * @param suspendProcessInstances If true, all the process instances of the provided process definition
     *                                will be suspended too.
     * @param suspensionDate The date on which the process definition will be suspended. If null, the
     *                       process definition is suspended immediately.
     *                       Note: The job executor needs to be active to use this!
     *
     * @throws ProcessEngineException
     *          If no such processDefinition can be found.
     * @throws AuthorizationException
     *           <li>If the user has no {@link Permissions#UPDATE} permission on {@link Resources#PROCESS_DEFINITION}.</li>
     *           <li>and if <code>suspendProcessInstances</code> is set to <code>true</code> and the user has none of the following:</li>
     *           <ul>
     *           <li>{@link ProcessInstancePermissions#SUSPEND} permission on {@link Resources#PROCESS_INSTANCE}</li>
     *           <li>{@link ProcessDefinitionPermissions#SUSPEND_INSTANCE} permission on {@link Resources#PROCESS_DEFINITION}</li>
     *           <li>{@link Permissions#UPDATE} permission on {@link Resources#PROCESS_INSTANCE}</li>
     *           <li>{@link Permissions#UPDATE_INSTANCE} permission on {@link Resources#PROCESS_DEFINITION}</li>
     *           </ul>
     *
     * @see RuntimeService#suspendProcessInstanceById(String)
     */
    public function suspendProcessDefinitionById(string $processDefinitionId, bool $suspendProcessInstances, string $suspensionDate): void;

    /**
     * Suspends the <strong>all</strong> process definitions with the given key (= id in the bpmn20.xml file).
     *
     * If a process definition is in state suspended, it will not be possible to start new process instances
     * based on the process definition.
     *
     * <p>Note: for more complex suspend commands use {@link #updateProcessDefinitionSuspensionState()}.</p>
     *
     * @param suspendProcessInstances If true, all the process instances of the provided process definition
     *                                will be suspended too.
     * @param suspensionDate The date on which the process definition will be suspended. If null, the
     *                       process definition is suspended immediately.
     *                       Note: The job executor needs to be active to use this!
     *
     * @throws ProcessEngineException
     *          If no such processDefinition can be found.
     * @throws AuthorizationException
     *           <li>If the user has no {@link Permissions#UPDATE} permission on {@link Resources#PROCESS_DEFINITION}.</li>
     *           <li>and if <code>suspendProcessInstances</code> is set to <code>true</code> and the user has none of the following:</li>
     *           <ul>
     *           <li>{@link ProcessInstancePermissions#SUSPEND} permission on {@link Resources#PROCESS_INSTANCE}</li>
     *           <li>{@link ProcessDefinitionPermissions#SUSPEND_INSTANCE} permission on {@link Resources#PROCESS_DEFINITION}</li>
     *           <li>{@link Permissions#UPDATE} permission on {@link Resources#PROCESS_INSTANCE}</li>
     *           <li>{@link Permissions#UPDATE_INSTANCE} permission on {@link Resources#PROCESS_DEFINITION}</li>
     *           </ul>
     *
     * @see RuntimeService#suspendProcessInstanceById(String)
     */
    public function suspendProcessDefinitionByKey(string $processDefinitionKey, ?bool $suspendProcessInstances = false, ?string $suspensionDate = null): void;

    /**
     * Activates the process definition with the given id.
     *
     * <p>Note: for more complex activate commands use {@link #updateProcessDefinitionSuspensionState()}.</p>
     *
     * @param suspendProcessInstances If true, all the process instances of the provided process definition
     *                                will be activated too.
     * @param activationDate The date on which the process definition will be activated. If null, the
     *                       process definition is suspended immediately.
     *                       Note: The job executor needs to be active to use this!
     *
     * @throws ProcessEngineException
     *          If no such processDefinition can be found.
     * @throws AuthorizationException
     *           <li>If the user has no {@link Permissions#UPDATE} permission on {@link Resources#PROCESS_DEFINITION}.</li>
     *           <li>and if <code>suspendProcessInstances</code> is set to <code>true</code> and the user has none of the following:</li>
     *           <ul>
     *           <li>{@link ProcessInstancePermissions#SUSPEND} permission on {@link Resources#PROCESS_INSTANCE}</li>
     *           <li>{@link ProcessDefinitionPermissions#SUSPEND_INSTANCE} permission on {@link Resources#PROCESS_DEFINITION}</li>
     *           <li>{@link Permissions#UPDATE} permission on {@link Resources#PROCESS_INSTANCE}</li>
     *           <li>{@link Permissions#UPDATE_INSTANCE} permission on {@link Resources#PROCESS_DEFINITION}</li>
     *           </ul>
     *
     * @see RuntimeService#activateProcessInstanceById(String)
     */
    public function activateProcessDefinitionById(string $processDefinitionId, ?bool $activateProcessInstances = false, ?string $activationDate = null): void;

    /**
     * Activates the process definition with the given key (=id in the bpmn20.xml file).
     *
     * <p>Note: for more complex activate commands use {@link #updateProcessDefinitionSuspensionState()}.</p>
     *
     * @param suspendProcessInstances If true, all the process instances of the provided process definition
     *                                will be activated too.
     * @param activationDate The date on which the process definition will be activated. If null, the
     *                       process definition is suspended immediately.
     *                       Note: The job executor needs to be active to use this!
     *
     * @throws ProcessEngineException
     *          If no such processDefinition can be found.
     * @throws AuthorizationException
     *           <li>If the user has no {@link Permissions#UPDATE} permission on {@link Resources#PROCESS_DEFINITION}.</li>
     *           <li>and if <code>suspendProcessInstances</code> is set to <code>true</code> and the user has none of the following:</li>
     *           <ul>
     *           <li>{@link ProcessInstancePermissions#SUSPEND} permission on {@link Resources#PROCESS_INSTANCE}</li>
     *           <li>{@link ProcessDefinitionPermissions#SUSPEND_INSTANCE} permission on {@link Resources#PROCESS_DEFINITION}</li>
     *           <li>{@link Permissions#UPDATE} permission on {@link Resources#PROCESS_INSTANCE}</li>
     *           <li>{@link Permissions#UPDATE_INSTANCE} permission on {@link Resources#PROCESS_DEFINITION}</li>
     *           </ul>
     *
     * @see RuntimeService#activateProcessInstanceById(String)
     */
    public function activateProcessDefinitionByKey(string $processDefinitionKey, ?bool $activateProcessInstances = false, ?string $activationDate = null): void;

    /**
     * Activate or suspend process definitions using a fluent builder. Specify the
     * definitions by calling one of the <i>by</i> methods, like
     * <i>byProcessDefinitionId</i>. To update the suspension state call
     * {@link UpdateProcessDefinitionSuspensionStateBuilder#activate()} or
     * {@link UpdateProcessDefinitionSuspensionStateBuilder#suspend()}.
     *
     * @return the builder to update the suspension state
     */
    public function updateProcessDefinitionSuspensionState(): UpdateProcessDefinitionSuspensionStateSelectBuilderInterface;

    /**
     * Updates time to live of process definition. The field is used within history cleanup process.
     * @param processDefinitionId
     * @param historyTimeToLive
     * @throws AuthorizationException
     *          If the user has no {@link Permissions#UPDATE} permission on {@link Resources#PROCESS_DEFINITION}.
     */
    public function updateProcessDefinitionHistoryTimeToLive(string $processDefinitionId, int $historyTimeToLive): void;

    /**
     * Gives access to a deployed process model, e.g., a BPMN 2.0 XML file,
     * through a stream of bytes.
     *
     * @param processDefinitionId
     *          id of a {@link ProcessDefinition}, cannot be null.
     *
     * @throws ProcessEngineException
     *           when the process model doesn't exist.
     * @throws AuthorizationException
     *          If the user has no {@link Permissions#READ} permission on {@link Resources#PROCESS_DEFINITION}.
     */
    public function getProcessModel(string $processDefinitionId);

    /**
     * Gives access to a deployed process diagram, e.g., a PNG image, through a
     * stream of bytes.
     *
     * @param processDefinitionId
     *          id of a {@link ProcessDefinition}, cannot be null.
     * @return null when the diagram resource name of a {@link ProcessDefinition} is null.
     *
     * @throws ProcessEngineException
     *           when the process diagram doesn't exist.
     * @throws AuthorizationException
     *          If the user has no {@link Permissions#READ} permission on {@link Resources#PROCESS_DEFINITION}.
     */
    public function getProcessDiagram(string $processDefinitionId);

    /**
     * Returns the {@link ProcessDefinition} including all BPMN information like additional
     * Properties (e.g. documentation).
     *
     * @throws AuthorizationException
     *          If the user has no {@link Permissions#READ} permission on {@link Resources#PROCESS_DEFINITION}.
     */
    public function getProcessDefinition(string $processDefinitionId): ProcessDefinitionInterface;

    /**
     * Provides positions and dimensions of elements in a process diagram as
     * provided by {@link RepositoryService#getProcessDiagram(String)}.
     *
     * This method requires a process model and a diagram image to be deployed.
     *
     * @param processDefinitionId id of a {@link ProcessDefinition}, cannot be null.
     * @return Map with process element ids as keys and positions and dimensions as values.
     *
     * @return null when the input stream of a process diagram is null.
     *
     * @throws ProcessEngineException
     *          When the process model or diagram doesn't exist.
     * @throws AuthorizationException
     *          If the user has no {@link Permissions#READ} permission on {@link Resources#PROCESS_DEFINITION}.
     */
    public function getProcessDiagramLayout(string $processDefinitionId): DiagramLayout;

    /**
     * Returns the {@link BpmnModelInstance} for the given processDefinitionId.
     *
     * @param processDefinitionId the id of the Process Definition for which the {@link BpmnModelInstance}
     *  should be retrieved.
     *
     * @return the {@link BpmnModelInstance}
     *
     * @throws AuthorizationException
     *          If the user has no {@link Permissions#READ} permission on {@link Resources#PROCESS_DEFINITION}.
     */
    public function getBpmnModelInstance(string $processDefinitionId): BpmnModelInstanceInterface;
}
