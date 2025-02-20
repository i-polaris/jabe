<?php

namespace Jabe\Engine\Impl\JobExecutor;

use Composer\Autoload\ClassLoader;
use parallel\Runtime;
use Ramsey\Uuid\Uuid;
use Jabe\Engine\Impl\{
    ProcessEngineImpl,
    ProcessEngineLogger
};
use Jabe\Engine\Impl\Interceptor\{
    CommandInterface,
    CommandExecutorInterface
};
use Jabe\Engine\Management\Metrics;
use Jabe\Engine\Runtime\JobInterface;
use Jabe\Engine\Impl\Util\ClassNameUtil;

abstract class JobExecutor
{
    //private final static JobExecutorLogger LOG = ProcessEngineLogger.JOB_EXECUTOR_LOGGER;

    protected $name;
    protected $processEngines = [];
    protected $acquireJobsCmdFactory;
    protected $acquireJobsRunnable;
    protected $rejectedJobsHandler;
    protected $jobAcquisitionThread;

    protected $isAutoActivate = false;
    protected $isActive = false;

    protected $maxJobsPerAcquisition = 3;

    // waiting when job acquisition is idle
    protected $waitTimeInMillis = 5 * 1000;
    protected $waitIncreaseFactor = 2;
    protected $maxWait = 60 * 1000;

    // backoff when job acquisition fails to lock all jobs
    protected $backoffTimeInMillis = 0;
    protected $maxBackoff = 0;

    /**
     * The number of job acquisition cycles without locking failures
     * until the backoff level is reduced.
     */
    protected $backoffDecreaseThreshold = 100;

    protected $lockOwner;
    protected $lockTimeInMillis = 5 * 60 * 1000;

    public function __construct()
    {
        $className = ClassNameUtil::getClassNameWithoutPackage(get_class($this));
        $this->name = "JobExecutor[$className]";
        $this->lockOwner = Uuid::uuid1();
    }

    public function start(): void
    {
        if ($this->isActive) {
            return;
        }
        //LOG.startingUpJobExecutor(getClass().getName());
        $this->ensureInitialization();
        $this->startExecutingJobs();
        $this->isActive = true;
    }

    public function shutdown(): void
    {
        if (!$this->isActive) {
            return;
        }
        //LOG.shuttingDownTheJobExecutor(getClass().getName());
        $this->acquireJobsRunnable->stop();
        $this->stopExecutingJobs();
        $this->ensureCleanup();
        $this->isActive = false;
    }

    protected function ensureInitialization(): void
    {
        if ($this->acquireJobsCmdFactory == null) {
            $this->acquireJobsCmdFactory =  new DefaultAcquireJobsCommandFactory($this);
        }
        $this->acquireJobsRunnable = new SequentialJobAcquisitionRunnable($this);
    }

    protected function ensureCleanup(): void
    {
        $this->acquireJobsCmdFactory = null;
        $this->acquireJobsRunnable = null;
    }

    public function jobWasAdded(): void
    {
        if ($this->isActive) {
            $this->acquireJobsRunnable->jobWasAdded();
        }
    }

    public function registerProcessEngine(ProcessEngineImpl $processEngine): void
    {
        $this->processEngines[] = $processEngine;

        // when we register the first process engine, start the jobexecutor
        if (count($processEngines) == 1 && $this->isAutoActivate) {
            $this->start();
        }
    }

    public function unregisterProcessEngine(ProcessEngineImpl $processEngine): void
    {
        //processEngines.remove(processEngine);
        foreach ($this->processEngines as $key => $engine) {
            if ($engine == $processEngine) {
                unset($this->processEngines[$key]);
                break;
            }
        }

        // if we unregister the last process engine, auto-shutdown the jobexecutor
        if (empty($this->processEngines) && $this->isActive) {
            $this->shutdown();
        }
    }

    abstract protected function startExecutingJobs(): void;
    abstract protected function stopExecutingJobs(): void;
    abstract public function executeJobs(array $jobIds, ?ProcessEngineImpl $processEngine = null): void;

    /**
     * Deprecated: use {@link #executeJobs(List, ProcessEngineImpl)} instead
     * @param jobIds
     */
    /*public function executeJobs(array $jobIds, ?ProcessEngineImpl $processEngine = null): void
    {
        if ($processEngine == null && !empty($this->processEngines)) {
            $this->executeJobs($jobIds, $this->processEngines[0]);
        }
    }*/

    public function logAcquisitionAttempt(ProcessEngineImpl $engine): void
    {
        if ($engine->getProcessEngineConfiguration()->isMetricsEnabled()) {
            $engine->getProcessEngineConfiguration()
            ->getMetricsRegistry()
            ->markOccurrence(Metrics::JOB_ACQUISITION_ATTEMPT);
        }
    }

    public function logAcquiredJobs(?ProcessEngineImpl $engine, int $numJobs): void
    {
        if ($engine != null && $engine->getProcessEngineConfiguration()->isMetricsEnabled()) {
            $engine->getProcessEngineConfiguration()
            ->getMetricsRegistry()
            ->markOccurrence(Metrics::JOB_ACQUIRED_SUCCESS, $numJobs);
        }
    }

    public function logAcquisitionFailureJobs(?ProcessEngineImpl $engine, int $numJobs): void
    {
        if ($engine != null && $engine->getProcessEngineConfiguration()->isMetricsEnabled()) {
            $engine->getProcessEngineConfiguration()
            ->getMetricsRegistry()
            ->markOccurrence(Metrics::JOB_ACQUIRED_FAILURE, $numJobs);
        }
    }

    public function logRejectedExecution(ProcessEngineImpl $engine, int $numJobs): void
    {
        if ($$engine != null && $engine->getProcessEngineConfiguration()->isMetricsEnabled()) {
            $engine->getProcessEngineConfiguration()
            ->getMetricsRegistry()
            ->markOccurrence(Metrics::JOB_EXECUTION_REJECTED, $numJobs);
        }
    }

    // getters and setters //////////////////////////////////////////////////////
    public function getProcessEngines(): array
    {
        return $this->processEngines;
    }

    /**
     * Must return an iterator of registered process engines
     * that is independent of concurrent modifications
     * to the underlying data structure of engines.
     */
    public function engineIterator(): array
    {
        // a CopyOnWriteArrayList's iterator is safe in the presence
        // of modifications
        return $this->processEngines;
    }

    public function hasRegisteredEngine(ProcessEngineImpl $engine): bool
    {
        return in_array($engine, $this->processEngines);
    }

    /**
     * Deprecated: use {@link #getProcessEngines()} instead
     */
    public function getCommandExecutor(): ?CommandExecutorInterface
    {
        if (empty($this->processEngines)) {
            return null;
        } else {
            return $this->processEngines[0]->getProcessEngineConfiguration()->getCommandExecutorTxRequired();
        }
    }

    /**
     * Deprecated: use {@link #registerProcessEngine(ProcessEngineImpl)} instead
     * @param commandExecutorTxRequired
     */
    public function setCommandExecutor(CommandExecutorInterface $commandExecutorTxRequired): void
    {
    }

    public function getWaitTimeInMillis(): int
    {
        return $this->waitTimeInMillis;
    }

    public function setWaitTimeInMillis(int $waitTimeInMillis): void
    {
        $this->waitTimeInMillis = $waitTimeInMillis;
    }

    public function getBackoffTimeInMillis(): int
    {
        return $this->backoffTimeInMillis;
    }

    public function setBackoffTimeInMillis(int $backoffTimeInMillis): void
    {
        $this->backoffTimeInMillis = $backoffTimeInMillis;
    }

    public function getLockTimeInMillis(): int
    {
        return $this->lockTimeInMillis;
    }

    public function setLockTimeInMillis(int $lockTimeInMillis): void
    {
        $this->lockTimeInMillis = $lockTimeInMillis;
    }

    public function getLockOwner(): string
    {
        return $this->lockOwner;
    }

    public function setLockOwner(string $lockOwner): void
    {
        $this->lockOwner = $lockOwner;
    }

    public function isAutoActivate(): bool
    {
        return $this->isAutoActivate;
    }

    public function setProcessEngines(array $processEngines): void
    {
        $this->processEngines = $processEngines;
    }

    public function setAutoActivate(bool $isAutoActivate): void
    {
        $this->isAutoActivate = $isAutoActivate;
    }

    public function getMaxJobsPerAcquisition(): int
    {
        return $this->maxJobsPerAcquisition;
    }

    public function setMaxJobsPerAcquisition(int $maxJobsPerAcquisition): void
    {
        $this->maxJobsPerAcquisition = $maxJobsPerAcquisition;
    }

    public function getWaitIncreaseFactor(): float
    {
        return $this->waitIncreaseFactor;
    }

    public function setWaitIncreaseFactor(float $waitIncreaseFactor): void
    {
        $this->waitIncreaseFactor = $waitIncreaseFactor;
    }

    public function getMaxWait(): int
    {
        return $this->maxWait;
    }

    public function setMaxWait(int $maxWait): void
    {
        $this->maxWait = $maxWait;
    }

    public function getMaxBackoff(): int
    {
        return $this->maxBackoff;
    }

    public function setMaxBackoff(int $maxBackoff): void
    {
        $this->maxBackoff = $maxBackoff;
    }

    public function getBackoffDecreaseThreshold(): int
    {
        return $this->backoffDecreaseThreshold;
    }

    public function setBackoffDecreaseThreshold(int $backoffDecreaseThreshold): void
    {
        $this->backoffDecreaseThreshold = $backoffDecreaseThreshold;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAcquireJobsCmd(int $numJobs): CommandInterface
    {
        return $this->acquireJobsCmdFactory->getCommand($numJobs);
    }

    public function getAcquireJobsCmdFactory(): AcquireJobsCommandFactoryInterface
    {
        return $this->acquireJobsCmdFactory;
    }

    public function setAcquireJobsCmdFactory(AcquireJobsCommandFactoryInterface $acquireJobsCmdFactory): void
    {
        $this->acquireJobsCmdFactory = $acquireJobsCmdFactory;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getRejectedJobsHandler(): RejectedJobsHandlerInterface
    {
        return $this->rejectedJobsHandler;
    }

    public function setRejectedJobsHandler(RejectedJobsHandlerInterface $rejectedJobsHandler): void
    {
        $this->rejectedJobsHandler = $rejectedJobsHandler;
    }

    protected function startJobAcquisitionThread(): void
    {
        if ($this->jobAcquisitionThread == null) {
            $scope = $this;
            $reflector = new \ReflectionClass(ClassLoader::class);
            $vendorPath = preg_replace('/^(.*)\/composer\/ClassLoader\.php$/', '$1', $reflector->getFileName());
            $this->jobAcquisitionThread = new Runtime($vendorPath . '/autoload.php');
            $this->jobAcquisitionThread->run(function () use ($scope) {
                $jobs = $scope->acquireJobsRunnable;
                $jobs->run();
            });
        }
    }

    protected function stopJobAcquisitionThread(): void
    {
        try {
            $this->jobAcquisitionThread->close();
        } catch (\Exception $e) {
            //LOG.interruptedWhileShuttingDownjobExecutor(e);
        }
        $this->jobAcquisitionThread = null;
    }

    public function getAcquireJobsRunnable(): AcquireJobsRunnable
    {
        return $this->acquireJobsRunnable;
    }

    public function getExecuteJobsRunnable(array $jobIds, ProcessEngineImpl $processEngine): RunnableInterface
    {
        return new ExecuteJobsRunnable($jobIds, $processEngine);
    }
}
