<?php

declare(strict_types=1);

namespace MLflow\Model;

/**
 * Represents an MLflow Dataset
 */
class Dataset
{
    private string $datasetId;

    private string $name;

    private ?string $experimentId;

    /** @var array<string, string>|null */
    private ?array $tags;

    private ?string $digest;

    private ?string $sourceType;

    private ?string $source;

    private ?string $schema;

    private ?string $profile;

    private ?int $creationTime;

    private ?int $lastUpdateTime;

    /**
     * @param array<string, string>|null $tags
     */
    public function __construct(
        string $datasetId,
        string $name,
        ?string $experimentId = null,
        ?array $tags = null,
        ?string $digest = null,
        ?string $sourceType = null,
        ?string $source = null,
        ?string $schema = null,
        ?string $profile = null,
        ?int $creationTime = null,
        ?int $lastUpdateTime = null
    ) {
        $this->datasetId = $datasetId;
        $this->name = $name;
        $this->experimentId = $experimentId;
        $this->tags = $tags;
        $this->digest = $digest;
        $this->sourceType = $sourceType;
        $this->source = $source;
        $this->schema = $schema;
        $this->profile = $profile;
        $this->creationTime = $creationTime;
        $this->lastUpdateTime = $lastUpdateTime;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $datasetId = $data['dataset_id'] ?? $data['id'] ?? '';
        $name = $data['name'] ?? '';
        $experimentId = $data['experiment_id'] ?? null;
        $tags = $data['tags'] ?? null;
        if (is_array($tags)) {
            /** @var array<string, string> $tags */
        } else {
            $tags = null;
        }
        $digest = $data['digest'] ?? null;
        $sourceType = $data['source_type'] ?? null;
        $source = $data['source'] ?? null;
        $schema = $data['schema'] ?? null;
        $profile = $data['profile'] ?? null;
        $creationTime = $data['creation_time'] ?? null;
        $lastUpdateTime = $data['last_update_time'] ?? null;

        return new self(
            is_string($datasetId) ? $datasetId : '',
            is_string($name) ? $name : '',
            is_string($experimentId) ? $experimentId : null,
            $tags,
            is_string($digest) ? $digest : null,
            is_string($sourceType) ? $sourceType : null,
            is_string($source) ? $source : null,
            is_string($schema) ? $schema : null,
            is_string($profile) ? $profile : null,
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
            'dataset_id' => $this->datasetId,
            'name' => $this->name,
        ];

        if ($this->experimentId !== null) {
            $data['experiment_id'] = $this->experimentId;
        }

        if ($this->tags !== null) {
            $data['tags'] = $this->tags;
        }

        if ($this->digest !== null) {
            $data['digest'] = $this->digest;
        }

        if ($this->sourceType !== null) {
            $data['source_type'] = $this->sourceType;
        }

        if ($this->source !== null) {
            $data['source'] = $this->source;
        }

        if ($this->schema !== null) {
            $data['schema'] = $this->schema;
        }

        if ($this->profile !== null) {
            $data['profile'] = $this->profile;
        }

        if ($this->creationTime !== null) {
            $data['creation_time'] = $this->creationTime;
        }

        if ($this->lastUpdateTime !== null) {
            $data['last_update_time'] = $this->lastUpdateTime;
        }

        return $data;
    }

    public function getDatasetId(): string
    {
        return $this->datasetId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getExperimentId(): ?string
    {
        return $this->experimentId;
    }

    /**
     * @return array<string, string>|null
     */
    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function getDigest(): ?string
    {
        return $this->digest;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function getSchema(): ?string
    {
        return $this->schema;
    }

    public function getProfile(): ?string
    {
        return $this->profile;
    }

    public function getCreationTime(): ?int
    {
        return $this->creationTime;
    }

    public function getLastUpdateTime(): ?int
    {
        return $this->lastUpdateTime;
    }
}
