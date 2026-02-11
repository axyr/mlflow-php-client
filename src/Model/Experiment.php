<?php

declare(strict_types=1);

namespace MLflow\Model;

use MLflow\Collection\TagCollection;
use MLflow\Enum\LifecycleStage;

/**
 * Represents an MLflow experiment
 */
readonly class Experiment
{
    public string $experimentId;
    public string $name;
    public ?string $artifactLocation;
    public ?LifecycleStage $lifecycleStage;
    /** @var TagCollection<ExperimentTag>|null */
    public ?TagCollection $tags;
    public ?int $creationTime;
    public ?int $lastUpdateTime;

    /**
     * @param string $experimentId
     * @param string $name
     * @param string|null $artifactLocation
     * @param LifecycleStage|null $lifecycleStage
     * @param TagCollection<ExperimentTag>|null $tags
     * @param int|null $creationTime
     * @param int|null $lastUpdateTime
     */
    public function __construct(
        string $experimentId,
        string $name,
        ?string $artifactLocation = null,
        ?LifecycleStage $lifecycleStage = null,
        ?TagCollection $tags = null,
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

        // Convert tags array to TagCollection
        $tags = null;
        if (isset($data['tags']) && is_array($data['tags'])) {
            $tagCollection = new TagCollection();
            foreach ($data['tags'] as $tagData) {
                if (is_array($tagData)) {
                    /** @phpstan-ignore-next-line Array shape validated */
                    $tagCollection->add(ExperimentTag::fromArray($tagData));
                }
            }
            $tags = $tagCollection;
        }

        $experimentId = $data['experiment_id'] ?? '';
        $name = $data['name'] ?? '';
        $creationTime = $data['creation_time'] ?? null;
        $lastUpdateTime = $data['last_update_time'] ?? null;

        return new self(
            is_string($experimentId) ? $experimentId : '',
            is_string($name) ? $name : '',
            $artifactLocation,
            $lifecycleStage,
            $tags,
            is_int($creationTime) ? $creationTime : (is_numeric($creationTime) ? (int) $creationTime : null),
            is_int($lastUpdateTime) ? $lastUpdateTime : (is_numeric($lastUpdateTime) ? (int) $lastUpdateTime : null)
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
            $data['tags'] = $this->tags->toArray();
        }

        if ($this->creationTime !== null) {
            $data['creation_time'] = $this->creationTime;
        }

        if ($this->lastUpdateTime !== null) {
            $data['last_update_time'] = $this->lastUpdateTime;
        }

        return $data;
    }

    public function isActive(): bool
    {
        return $this->lifecycleStage?->isActive() ?? true;
    }

    public function isDeleted(): bool
    {
        return $this->lifecycleStage?->isDeleted() ?? false;
    }

    // Legacy methods for backwards compatibility (deprecated)
    /** @deprecated Access $experimentId property directly */
    public function getExperimentId(): string
    {
        return $this->experimentId;
    }

    /** @deprecated Access $name property directly */
    public function getName(): string
    {
        return $this->name;
    }

    /** @deprecated Access $artifactLocation property directly */
    public function getArtifactLocation(): ?string
    {
        return $this->artifactLocation;
    }

    /** @deprecated Access $lifecycleStage property directly */
    public function getLifecycleStage(): ?LifecycleStage
    {
        return $this->lifecycleStage;
    }

    /**
     * @deprecated Access $tags property directly. Note: Returns TagCollection now, not array
     * @return TagCollection<ExperimentTag>|null
     */
    public function getTags(): ?TagCollection
    {
        return $this->tags;
    }

    /** @deprecated Access $creationTime property directly */
    public function getCreationTime(): ?int
    {
        return $this->creationTime;
    }

    /** @deprecated Access $lastUpdateTime property directly */
    public function getLastUpdateTime(): ?int
    {
        return $this->lastUpdateTime;
    }
}
