<?php

declare(strict_types=1);

namespace MLflow\Model;

use MLflow\Enum\LifecycleStage;

/**
 * Represents an MLflow experiment
 */
class Experiment
{
    private string $experimentId;
    private string $name;
    private ?string $artifactLocation;
    private ?LifecycleStage $lifecycleStage;
    /** @var array<string, mixed>|null */
    private ?array $tags;
    private ?int $creationTime;
    private ?int $lastUpdateTime;

    /**
     * @param string $experimentId
     * @param string $name
     * @param string|null $artifactLocation
     * @param LifecycleStage|null $lifecycleStage
     * @param array<string, mixed>|null $tags
     * @param int|null $creationTime
     * @param int|null $lastUpdateTime
     */
    public function __construct(
        string $experimentId,
        string $name,
        ?string $artifactLocation = null,
        ?LifecycleStage $lifecycleStage = null,
        ?array $tags = null,
        ?int $creationTime = null,
        ?int $lastUpdateTime = null
    ) {
        $this->experimentId = $experimentId;
        $this->name = $name;
        $this->artifactLocation = $artifactLocation;
        $this->lifecycleStage = $lifecycleStage;
        $this->tags = $tags;
        $this->creationTime = $creationTime;
        $this->lastUpdateTime = $lastUpdateTime;
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $lifecycleStage = null;
        if (isset($data['lifecycle_stage']) && is_string($data['lifecycle_stage'])) {
            $lifecycleStage = LifecycleStage::from($data['lifecycle_stage']);
        }

        $artifactLocation = $data['artifact_location'] ?? null;
        if ($artifactLocation !== null && !is_string($artifactLocation)) {
            $artifactLocation = null;
        }

        $tags = $data['tags'] ?? null;
        if ($tags !== null && !is_array($tags)) {
            $tags = null;
        }

        return new self(
            (string) ($data['experiment_id'] ?? ''),
            (string) ($data['name'] ?? ''),
            $artifactLocation,
            $lifecycleStage,
            $tags,
            isset($data['creation_time']) ? (int) $data['creation_time'] : null,
            isset($data['last_update_time']) ? (int) $data['last_update_time'] : null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'experiment_id' => $this->experimentId,
            'name' => $this->name,
        ];

        if ($this->artifactLocation !== null) {
            $data['artifact_location'] = $this->artifactLocation;
        }

        if ($this->lifecycleStage !== null) {
            $data['lifecycle_stage'] = $this->lifecycleStage->value;
        }

        if ($this->tags !== null) {
            $data['tags'] = $this->tags;
        }

        if ($this->creationTime !== null) {
            $data['creation_time'] = $this->creationTime;
        }

        if ($this->lastUpdateTime !== null) {
            $data['last_update_time'] = $this->lastUpdateTime;
        }

        return $data;
    }

    // Getters
    public function getExperimentId(): string
    {
        return $this->experimentId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getArtifactLocation(): ?string
    {
        return $this->artifactLocation;
    }

    public function getLifecycleStage(): ?LifecycleStage
    {
        return $this->lifecycleStage;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function getCreationTime(): ?int
    {
        return $this->creationTime;
    }

    public function getLastUpdateTime(): ?int
    {
        return $this->lastUpdateTime;
    }

    public function isActive(): bool
    {
        return $this->lifecycleStage?->isActive() ?? true;
    }

    public function isDeleted(): bool
    {
        return $this->lifecycleStage?->isDeleted() ?? false;
    }
}