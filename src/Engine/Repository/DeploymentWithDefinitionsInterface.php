<?php

namespace Jabe\Engine\Repository;

interface DeploymentWithDefinitionsInterface extends DeploymentInterface
{
    /**
     * Returns the process definitions, which are deployed with that deployment.
     *
     * @return the process definitions which are deployed
     */
    public function getDeployedProcessDefinitions(): array;

    /**
     * Returns the case definitions, which are deployed with that deployment.
     *
     * @return the case definitions, which are deployed
     */
    public function getDeployedCaseDefinitions(): array;

    /**
     * Returns the decision definitions, which are deployed with that deployment
     *
     * @return the decision definitions, which are deployed
     */
    public function getDeployedDecisionDefinitions(): array;

    /**
     * Returns the decision requirements definitions, which are deployed with that deployment
     *
     * @return the decision definitions, which are deployed
     */
    public function getDeployedDecisionRequirementsDefinitions(): array;
}
