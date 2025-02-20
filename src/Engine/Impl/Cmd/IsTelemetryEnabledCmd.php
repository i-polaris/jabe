<?php

namespace Jabe\Engine\Impl\Cmd;

use Jabe\Engine\Impl\ProcessEngineLogger;
use Jabe\Engine\Impl\Interceptor\{
    CommandInterface,
    CommandContext
};

class IsTelemetryEnabledCmd implements CommandInterface
{
    //protected static final TelemetryLogger LOG = ProcessEngineLogger.TELEMETRY_LOGGER;

    public function execute(CommandContext $commandContext)
    {
        $authorizationManager = $commandContext->getAuthorizationManager();
        $authorizationManager->checkAdminOrPermission("checkReadTelemetryCollectionStatusData");

        $telemetryProperty = $commandContext->getPropertyManager()->findPropertyById("telemetry.enabled");
        if ($telemetryProperty != null) {
            if (strtolower($telemetryProperty->getValue()) == "null") {
                return null;
            } else {
                return boolval($telemetryProperty->getValue());
            }
        } else {
            //LOG.databaseTelemetryPropertyMissingInfo();
            return null;
        }
    }
}
