<?php

namespace Jabe\Engine\Repository;

interface UpdateProcessDefinitionSuspensionStateTenantBuilderInterface
{
    /**
     * Specify that the process definition belongs to no tenant.
     *
     * @return the builder
     */
    public function processDefinitionWithoutTenantId(): UpdateProcessDefinitionSuspensionStateBuilderInterface;

    /**
     * Specify the id of the tenant the process definition belongs to.
     *
     * @param tenantId
     *          the id of the tenant
     * @return the builder
     */
    public function processDefinitionTenantId(string $tenantId): UpdateProcessDefinitionSuspensionStateBuilderInterface;
}
