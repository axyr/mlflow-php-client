<?php

declare(strict_types=1);

namespace MLflow\Model;

use MLflow\Enum\RunStatus;
use MLflow\Enum\LifecycleStage;

/**
 * Represents metadata about an MLflow run
 */
class RunInfo
{
    private string $runId;
    private string $experimentId;
    private RunStatus $status;
    private int $startTime;
    private ?int $endTime;
    private ?string $artifactUri;
    private ?string $userId;
    private ?string $runName;
    private ?LifecycleStage $lifecycleStage;

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
     * Create RunInfo from an array
     */
    public static function fromArray(array $data): self
    {
        $status = RunStatus::from($data['status']);

        $lifecycleStage = null;
        if (isset($data['lifecycle_stage'])) {
            $lifecycleStage = LifecycleStage::from($data['lifecycle_stage']);
        }

        return new self(
            $data['run_id'] ?? $data['run_uuid'], // Support both old and new field names
            $data['experiment_id'],
            $status,
            (int) $data['start_time'],
            isset($data['end_time']) ? (int) $data['end_time'] : null,
            $data['artifact_uri'] ?? null,
            $data['user_id'] ?? null,
            $data['run_name'] ?? null,
            $lifecycleStage
        );
    }

    /**
     * Convert to array
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

    // Getters
    public function getRunId(): string
    {
        return $this->runId;
    }

    public function getExperimentId(): string
    {
        return $this->experimentId;
    }

    public function getStatus(): RunStatus
    {
        return $this->status;
    }

    public function getStartTime(): int
    {
        return $this->startTime;
    }

    public function getEndTime(): ?int
    {
        return $this->endTime;
    }

    public function getArtifactUri(): ?string
    {
        return $this->artifactUri;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getRunName(): ?string
    {
        return $this->runName;
    }

    public function getLifecycleStage(): LifecycleStage
    {
        return $this->lifecycleStage;
    }

    public function isRunning(): bool
    {
        return $this->status === RunStatus::RUNNING;
    }

    public function isFinished(): bool
    {
        return $this->status->isTerminal();
    }
}