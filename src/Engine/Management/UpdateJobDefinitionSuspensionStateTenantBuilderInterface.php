<?php

namespace BpmPlatform\Engine\Management;

interface UpdateJobDefinitionSuspensionStateTenantBuilderInterface
{
    /**
     * Specify that the process definition belongs to no tenant.
     *
     * @return the builder
     */
    public function processDefinitionWithoutTenantId(): UpdateJobDefinitionSuspensionStateBuilderInterface;

    /**
     * Specify the id of the tenant the process definition belongs to.
     *
     * @param tenantId
     *          the id of the tenant
     * @return the builder
     */
    public function processDefinitionTenantId(string $tenantId): UpdateJobDefinitionSuspensionStateBuilderInterface;
}
