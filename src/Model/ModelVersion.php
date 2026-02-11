<?php

declare(strict_types=1);

namespace MLflow\Model;

use MLflow\Collection\TagCollection;
use MLflow\Enum\ModelStage;
use MLflow\Enum\ModelVersionStatus;

/**
 * Represents a model version in MLflow Model Registry
 */
readonly class ModelVersion
{
    public string $name;

    public string $version;

    public ?int $creationTimestamp;

    public ?int $lastUpdatedTimestamp;

    public ?ModelStage $currentStage;

    public ?string $description;

    public ?string $source;

    public ?string $runId;

    public ?ModelVersionStatus $status;

    public ?string $statusMessage;

    /** @var TagCollection<ModelTag>|null */
    public ?TagCollection $tags;

    public ?string $runLink;

    /** @var array<string>|null */
    public ?array $aliases;

    /**
     * @param TagCollection<ModelTag>|null $tags
     * @param array<string>|null           $aliases
     */
    public function __construct(
        string $name,
        string $version,
        ?int $creationTimestamp = null,
        ?int $lastUpdatedTimestamp = null,
        ?ModelStage $currentStage = null,
        ?string $description = null,
        ?string $source = null,
        ?string $runId = null,
        ?ModelVersionStatus $status = null,
        ?string $statusMessage = null,
        ?TagCollection $tags = null,
        ?string $runLink = null,
        ?array $aliases = null
    ) {
        $this->name = $name;
        $this->version = $version;
        $this->creationTimestamp = $creationTimestamp;
        $this->lastUpdatedTimestamp = $lastUpdatedTimestamp;
        $this->currentStage = $currentStage;
        $this->description = $description;
        $this->source = $source;
        $this->runId = $runId;
        $this->status = $status;
        $this->statusMessage = $statusMessage;
        $this->tags = $tags;
        $this->runLink = $runLink;
        $this->aliases = $aliases;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $tags = null;
        if (isset($data['tags']) && is_array($data['tags'])) {
            $tagCollection = new TagCollection;
            foreach ($data['tags'] as $tagData) {
                if (is_array($tagData)) {
                    /** @phpstan-ignore-next-line Array shape validated */
                    $tagCollection->add(ModelTag::fromArray($tagData));
                }
            }
            $tags = $tagCollection;
        }

        $currentStageStr = $data['current_stage'] ?? null;
        $currentStage = null;
        if (is_string($currentStageStr) && $currentStageStr !== '') {
            $currentStage = ModelStage::tryFrom($currentStageStr);
        }

        $statusStr = $data['status'] ?? null;
        $status = null;
        if (is_string($statusStr) && $statusStr !== '') {
            $status = ModelVersionStatus::tryFrom($statusStr);
        }

        $description = $data['description'] ?? null;
        $source = $data['source'] ?? null;
        $runId = $data['run_id'] ?? null;
        $statusMessage = $data['status_message'] ?? null;
        $runLink = $data['run_link'] ?? null;
        $aliases = $data['aliases'] ?? null;
        if (is_array($aliases)) {
            /** @var array<string> $aliases */
        } else {
            $aliases = null;
        }

        $name = $data['name'] ?? '';
        $version = $data['version'] ?? '';
        $creationTimestamp = $data['creation_timestamp'] ?? null;
        $lastUpdatedTimestamp = $data['last_updated_timestamp'] ?? null;

        if (is_int($creationTimestamp)) {
            $creationTs = $creationTimestamp;
        } elseif (is_numeric($creationTimestamp)) {
            $creationTs = (int) $creationTimestamp;
        } else {
            $creationTs = null;
        }

        if (is_int($lastUpdatedTimestamp)) {
            $lastUpdatedTs = $lastUpdatedTimestamp;
        } elseif (is_numeric($lastUpdatedTimestamp)) {
            $lastUpdatedTs = (int) $lastUpdatedTimestamp;
        } else {
            $lastUpdatedTs = null;
        }

        return new self(
            is_string($name) ? $name : '',
            is_string($version) ? $version : '',
            $creationTs,
            $lastUpdatedTs,
            $currentStage,
            is_string($description) ? $description : null,
            is_string($source) ? $source : null,
            is_string($runId) ? $runId : null,
            $status,
            is_string($statusMessage) ? $statusMessage : null,
            $tags,
            is_string($runLink) ? $runLink : null,
            $aliases
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'version' => $this->version,
        ];

        if ($this->creationTimestamp !== null) {
            $data['creation_timestamp'] = $this->creationTimestamp;
        }

        if ($this->lastUpdatedTimestamp !== null) {
            $data['last_updated_timestamp'] = $this->lastUpdatedTimestamp;
        }

        if ($this->currentStage !== null) {
            $data['current_stage'] = $this->currentStage->value;
        }

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->source !== null) {
            $data['source'] = $this->source;
        }

        if ($this->runId !== null) {
            $data['run_id'] = $this->runId;
        }

        if ($this->status !== null) {
            $data['status'] = $this->status->value;
        }

        if ($this->statusMessage !== null) {
            $data['status_message'] = $this->statusMessage;
        }

        if ($this->tags !== null) {
            $data['tags'] = $this->tags->toArray();
        }

        if ($this->runLink !== null) {
            $data['run_link'] = $this->runLink;
        }

        if ($this->aliases !== null) {
            $data['aliases'] = $this->aliases;
        }

        return $data;
    }

    // Status checks
    public function isReady(): bool
    {
        return $this->status?->isReady() ?? false;
    }

    public function isPending(): bool
    {
        return $this->status?->isPending() ?? false;
    }

    public function isFailed(): bool
    {
        return $this->status?->isFailed() ?? false;
    }

    // Stage checks
    public function isInProduction(): bool
    {
        return $this->currentStage === ModelStage::PRODUCTION;
    }

    public function isInStaging(): bool
    {
        return $this->currentStage === ModelStage::STAGING;
    }

    public function isArchived(): bool
    {
        return $this->currentStage === ModelStage::ARCHIVED;
    }
}
