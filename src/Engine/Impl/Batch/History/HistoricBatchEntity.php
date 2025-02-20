<?php

namespace Jabe\Engine\Impl\Batch\History;

use Jabe\Engine\Batch\History\HistoricBatchInterface;
use Jabe\Engine\Impl\Context\Context;
use Jabe\Engine\Impl\Db\DbEntityInterface;
use Jabe\Engine\Impl\History\Event\HistoryEvent;
use Jabe\Engine\Impl\Persistence\Entity\{
    HistoricIncidentManager,
    HistoricJobLogManager
};

class HistoricBatchEntity extends HistoryEvent implements HistoricBatchInterface, DbEntityInterface
{
    protected $id;
    protected $type;

    protected $totalJobs;
    protected $batchJobsPerSeed;
    protected $invocationsPerBatchJob;

    protected $seedJobDefinitionId;
    protected $monitorJobDefinitionId;
    protected $batchJobDefinitionId;

    protected $tenantId;
    protected $createUserId;

    protected $startTime;
    protected $endTime;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getTotalJobs(): int
    {
        return $this->totalJobs;
    }

    public function setTotalJobs(int $totalJobs): void
    {
        $this->totalJobs = $totalJobs;
    }

    public function getBatchJobsPerSeed(): int
    {
        return $this->batchJobsPerSeed;
    }

    public function setBatchJobsPerSeed(int $batchJobsPerSeed): void
    {
        $this->batchJobsPerSeed = $batchJobsPerSeed;
    }

    public function getInvocationsPerBatchJob(): int
    {
        return $this->invocationsPerBatchJob;
    }

    public function setInvocationsPerBatchJob(int $invocationsPerBatchJob): void
    {
        $this->invocationsPerBatchJob = $invocationsPerBatchJob;
    }

    public function getSeedJobDefinitionId(): string
    {
        return $this->seedJobDefinitionId;
    }

    public function setSeedJobDefinitionId(string $seedJobDefinitionId): void
    {
        $this->seedJobDefinitionId = $seedJobDefinitionId;
    }

    public function getMonitorJobDefinitionId(): string
    {
        return $this->monitorJobDefinitionId;
    }

    public function setMonitorJobDefinitionId(string $monitorJobDefinitionId): void
    {
        $this->monitorJobDefinitionId = $monitorJobDefinitionId;
    }

    public function getBatchJobDefinitionId(): string
    {
        return $this->batchJobDefinitionId;
    }

    public function setBatchJobDefinitionId(string $batchJobDefinitionId): void
    {
        $this->batchJobDefinitionId = $batchJobDefinitionId;
    }

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    public function setTenantId(string $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function getCreateUserId(): string
    {
        return $this->createUserId;
    }

    public function setCreateUserId(string $createUserId): void
    {
        $this->createUserId = $createUserId;
    }

    public function getStartTime(): string
    {
        return $this->startTime;
    }

    public function setStartTime(string $startTime): void
    {
        $this->startTime = $startTime;
    }

    public function getEndTime(): string
    {
        return $this->endTime;
    }

    public function setEndTime(string $endTime): void
    {
        $this->endTime = $endTime;
    }

    public function getPersistentState()
    {
        $persistentState = [];

        $persistentState["endTime"] = $endTime;

        return $persistentState;
    }

    public function delete(): void
    {
        $historicIncidentManager = Context::getCommandContext()->getHistoricIncidentManager();
        $historicIncidentManager->deleteHistoricIncidentsByJobDefinitionId($seedJobDefinitionId);
        $historicIncidentManager->deleteHistoricIncidentsByJobDefinitionId($monitorJobDefinitionId);
        $historicIncidentManager->deleteHistoricIncidentsByJobDefinitionId($batchJobDefinitionId);

        $historicJobLogManager = Context::getCommandContext()->getHistoricJobLogManager();
        $historicJobLogManager->deleteHistoricJobLogsByJobDefinitionId($seedJobDefinitionId);
        $historicJobLogManager->deleteHistoricJobLogsByJobDefinitionId($monitorJobDefinitionId);
        $historicJobLogManager->deleteHistoricJobLogsByJobDefinitionId($batchJobDefinitionId);

        Context::getCommandContext()->getHistoricBatchManager()->delete($this);
    }
}
