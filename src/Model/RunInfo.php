<?php

declare(strict_types=1);

namespace MLflow\Model;

use MLflow\Enum\LifecycleStage;
use MLflow\Enum\RunStatus;
use MLflow\Util\ValidationHelper;

/**
 * Represents metadata about an MLflow run
 */
readonly class RunInfo
{
    public string $runId;

    public string $experimentId;

    public RunStatus $status;

    public int $startTime;

    public ?int $endTime;

    public ?string $artifactUri;

    public ?string $userId;

    public ?string $runName;

    public LifecycleStage $lifecycleStage;

    public function __construct(
        string $runId,
        string $experimentId,
        RunStatus $status,
        int $startTime,
        ?int $endTime = null,
        ?string $artifactUri = null,
        ?string $userId = null,
        ?string $runName = null,
        ?LifecycleStage $lifecycleStage = null
    ) {
        $this->runId = $runId;
        $this->experimentId = $experimentId;
        $this->status = $status;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->artifactUri = $artifactUri;
        $this->userId = $userId;
        $this->runName = $runName;
        $this->lifecycleStage = $lifecycleStage ?? LifecycleStage::ACTIVE;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $statusValue = $data['status'] ?? 'RUNNING';
        $status = is_string($statusValue) || is_int($statusValue) ? RunStatus::from($statusValue) : RunStatus::RUNNING;

        $lifecycleStage = null;
        if (isset($data['lifecycle_stage'])) {
            $lsValue = $data['lifecycle_stage'];
            if (is_string($lsValue) || is_int($lsValue)) {
                $lifecycleStage = LifecycleStage::from($lsValue);
            }
        }

        return new self(
            ValidationHelper::requireString($data, 'run_id', 'run_uuid'),
            ValidationHelper::requireString($data, 'experiment_id'),
            $status,
            ValidationHelper::optionalInt($data, 'start_time') ?? 0,
            ValidationHelper::optionalInt($data, 'end_time'),
            ValidationHelper::optionalString($data, 'artifact_uri'),
            ValidationHelper::optionalString($data, 'user_id'),
            ValidationHelper::optionalString($data, 'run_name'),
            $lifecycleStage
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'run_id' => $this->runId,
            'experiment_id' => $this->experimentId,
            'status' => $this->status->value,
            'start_time' => $this->startTime,
            'lifecycle_stage' => $this->lifecycleStage->value,
        ];

        if ($this->endTime !== null) {
            $data['end_time'] = $this->endTime;
        }

        if ($this->artifactUri !== null) {
            $data['artifact_uri'] = $this->artifactUri;
        }

        if ($this->userId !== null) {
            $data['user_id'] = $this->userId;
        }

        if ($this->runName !== null) {
            $data['run_name'] = $this->runName;
        }

        return $data;
    }

    public function isRunning(): bool
    {
        return $this->status === RunStatus::RUNNING;
    }

    public function isFinished(): bool
    {
        return $this->status->isTerminal();
    }

    // Legacy methods for backwards compatibility (deprecated)
    /** @deprecated Access $runId property directly */
    public function getRunId(): string
    {
        return $this->runId;
    }

    /** @deprecated Access $experimentId property directly */
    public function getExperimentId(): string
    {
        return $this->experimentId;
    }

    /** @deprecated Access $status property directly */
    public function getStatus(): RunStatus
    {
        return $this->status;
    }

    /** @deprecated Access $startTime property directly */
    public function getStartTime(): int
    {
        return $this->startTime;
    }

    /** @deprecated Access $endTime property directly */
    public function getEndTime(): ?int
    {
        return $this->endTime;
    }

    /** @deprecated Access $artifactUri property directly */
    public function getArtifactUri(): ?string
    {
        return $this->artifactUri;
    }

    /** @deprecated Access $userId property directly */
    public function getUserId(): ?string
    {
        return $this->userId;
    }

    /** @deprecated Access $runName property directly */
    public function getRunName(): ?string
    {
        return $this->runName;
    }

    /** @deprecated Access $lifecycleStage property directly */
    public function getLifecycleStage(): LifecycleStage
    {
        return $this->lifecycleStage;
    }
}
