<?php

declare(strict_types=1);

namespace MLflow\Model;

use MLflow\Enum\RunStatus;
use MLflow\Enum\LifecycleStage;

/**
 * Represents an MLflow run
 */
class Run
{
    private RunInfo $info;
    private RunData $data;
    private ?array $inputs;

    public function __construct(RunInfo $info, RunData $data, ?array $inputs = null)
    {
        $this->info = $info;
        $this->data = $data;
        $this->inputs = $inputs;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            RunInfo::fromArray($data['info']),
            RunData::fromArray($data['data'] ?? []),
            $data['inputs'] ?? null
        );
    }

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
    public function getInfo(): RunInfo
    {
        return $this->info;
    }

    public function getData(): RunData
    {
        return $this->data;
    }

    public function getInputs(): ?array
    {
        return $this->inputs;
    }

    // Convenience methods
    public function getRunId(): string
    {
        return $this->info->getRunId();
    }

    public function getExperimentId(): string
    {
        return $this->info->getExperimentId();
    }

    public function getStatus(): RunStatus
    {
        return $this->info->getStatus();
    }

    public function getStartTime(): int
    {
        return $this->info->getStartTime();
    }

    public function getEndTime(): ?int
    {
        return $this->info->getEndTime();
    }

    public function getArtifactUri(): ?string
    {
        return $this->info->getArtifactUri();
    }

    public function getMetrics(): array
    {
        return $this->data->getMetrics();
    }

    public function getParams(): array
    {
        return $this->data->getParams();
    }

    public function getTags(): array
    {
        return $this->data->getTags();
    }

    public function isActive(): bool
    {
        return $this->info->getLifecycleStage() === 'active';
    }

    public function isDeleted(): bool
    {
        return $this->info->getLifecycleStage() === 'deleted';
    }
}