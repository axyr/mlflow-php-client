<?php

declare(strict_types=1);

namespace MLflow\Model;

use MLflow\Enum\RunStatus;
use MLflow\Enum\LifecycleStage;

/**
 * Represents an MLflow run
 */
readonly class Run
{
    public RunInfo $info;
    public RunData $data;
    /** @var array<string, mixed>|null */
    public ?array $inputs;

    /**
     * @param RunInfo $info
     * @param RunData $data
     * @param array<string, mixed>|null $inputs
     */
    public function __construct(RunInfo $info, RunData $data, ?array $inputs = null)
    {
        $this->info = $info;
        $this->data = $data;
        $this->inputs = $inputs;
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $info = $data['info'] ?? [];
        $dataArray = $data['data'] ?? [];
        $inputs = $data['inputs'] ?? null;

        if (!is_array($info)) {
            $info = [];
        }
        if (!is_array($dataArray)) {
            $dataArray = [];
        }
        if ($inputs !== null && !is_array($inputs)) {
            $inputs = null;
        }

        return new self(
            RunInfo::fromArray($info),
            RunData::fromArray($dataArray),
            $inputs
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'info' => $this->info->toArray(),
            'data' => $this->data->toArray(),
        ];

        if ($this->inputs !== null) {
            $data['inputs'] = $this->inputs;
        }

        return $data;
    }

    // Getters
    /** @deprecated Access $info property directly */
    public function getInfo(): RunInfo
    {
        return $this->info;
    }

    /** @deprecated Access $data property directly */
    public function getData(): RunData
    {
        return $this->data;
    }

    /**
     * @return array<string, mixed>|null
     * @deprecated Access $inputs property directly
     */
    public function getInputs(): ?array
    {
        return $this->inputs;
    }

    // Convenience methods
    public function getRunId(): string
    {
        return $this->info->runId;
    }

    public function getExperimentId(): string
    {
        return $this->info->experimentId;
    }

    public function getStatus(): RunStatus
    {
        return $this->info->status;
    }

    public function getStartTime(): int
    {
        return $this->info->startTime;
    }

    public function getEndTime(): ?int
    {
        return $this->info->endTime;
    }

    public function getArtifactUri(): ?string
    {
        return $this->info->artifactUri;
    }

    /**
     * @return array<Metric>
     */
    public function getMetrics(): array
    {
        return $this->data->metrics->all();
    }

    /**
     * @return array<Param>
     */
    public function getParams(): array
    {
        return array_values($this->data->params->all());
    }

    /**
     * @return array<RunTag>
     */
    public function getTags(): array
    {
        return array_values($this->data->tags->all());
    }

    public function isActive(): bool
    {
        return $this->info->lifecycleStage === LifecycleStage::ACTIVE;
    }

    public function isDeleted(): bool
    {
        return $this->info->lifecycleStage === LifecycleStage::DELETED;
    }
}
