<?php

namespace Jabe\Engine\Migration;

interface MigrationInstructionInterface
{
    /**
     * @return the id of the activity of the source process definition that this
     * instruction maps instances from
     */
    public function getSourceActivityId(): string;

    /**
     * @return the id of the activity of the target process definition that this
     * instruction maps instances to
     */
    public function getTargetActivityId(): string;

    /**
     * @return whether this flow node's event trigger is going to be updated during
     *   migration. Can only be true for flow nodes that define a persistent event trigger.
     *   See {@link MigrationInstructionBuilder#updateEventTrigger()} for details
     */
    public function isUpdateEventTrigger(): bool;
}
