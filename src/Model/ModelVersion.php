<?php

declare(strict_types=1);

namespace MLflow\Model;

/**
 * Represents a model version in MLflow Model Registry
 */
class ModelVersion
{
    private string $name;
    private string $version;
    private ?int $creationTimestamp;
    private ?int $lastUpdatedTimestamp;
    private ?string $currentStage;
    private ?string $description;
    private ?string $source;
    private ?string $runId;
    private ?string $status;
    private ?string $statusMessage;
    /** @var array<ModelTag>|null */
    private ?array $tags;
    private ?string $runLink;
    /** @var array<string>|null */
    private ?array $aliases;

    /**
     * @param string $name
     * @param string $version
     * @param int|null $creationTimestamp
     * @param int|null $lastUpdatedTimestamp
     * @param string|null $currentStage
     * @param string|null $description
     * @param string|null $source
     * @param string|null $runId
     * @param string|null $status
     * @param string|null $statusMessage
     * @param array<ModelTag>|null $tags
     * @param string|null $runLink
     * @param array<string>|null $aliases
     */
    public function __construct(
        string $name,
        string $version,
        ?int $creationTimestamp = null,
        ?int $lastUpdatedTimestamp = null,
        ?string $currentStage = null,
        ?string $description = null,
        ?string $source = null,
        ?string $runId = null,
        ?string $status = null,
        ?string $statusMessage = null,
        ?array $tags = null,
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
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $tags = null;
        if (isset($data['tags']) && is_array($data['tags'])) {
            $tags = [];
            foreach ($data['tags'] as $tagData) {
                if (is_array($tagData)) {
                    /** @phpstan-ignore-next-line Array shape validated */
                    $tags[] = ModelTag::fromArray($tagData);
                }
            }
        }

        $currentStage = $data['current_stage'] ?? null;
        $description = $data['description'] ?? null;
        $source = $data['source'] ?? null;
        $runId = $data['run_id'] ?? null;
        $status = $data['status'] ?? null;
        $statusMessage = $data['status_message'] ?? null;
        $runLink = $data['run_link'] ?? null;
        $aliases = $data['aliases'] ?? null;

        $name = $data['name'] ?? '';
        $version = $data['version'] ?? '';
        $creationTimestamp = $data['creation_timestamp'] ?? null;
        $lastUpdatedTimestamp = $data['last_updated_timestamp'] ?? null;

        return new self(
            is_string($name) ? $name : '',
            is_string($version) ? $version : '',
            is_int($creationTimestamp) ? $creationTimestamp : (is_numeric($creationTimestamp) ? (int) $creationTimestamp : null),
            is_int($lastUpdatedTimestamp) ? $lastUpdatedTimestamp : (is_numeric($lastUpdatedTimestamp) ? (int) $lastUpdatedTimestamp : null),
            is_string($currentStage) ? $currentStage : null,
            is_string($description) ? $description : null,
            is_string($source) ? $source : null,
            is_string($runId) ? $runId : null,
            is_string($status) ? $status : null,
            is_string($statusMessage) ? $statusMessage : null,
            $tags,
            is_string($runLink) ? $runLink : null,
            is_array($aliases) ? $aliases : null
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
            $data['current_stage'] = $this->currentStage;
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
            $data['status'] = $this->status;
        }

        if ($this->statusMessage !== null) {
            $data['status_message'] = $this->statusMessage;
        }

        if ($this->tags !== null) {
            $data['tags'] = array_map(fn($t) => $t->toArray(), $this->tags);
        }

        if ($this->runLink !== null) {
            $data['run_link'] = $this->runLink;
        }

        if ($this->aliases !== null) {
            $data['aliases'] = $this->aliases;
        }

        return $data;
    }

    // Getters
    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getCreationTimestamp(): ?int
    {
        return $this->creationTimestamp;
    }

    public function getLastUpdatedTimestamp(): ?int
    {
        return $this->lastUpdatedTimestamp;
    }

    public function getCurrentStage(): ?string
    {
        return $this->currentStage;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function getRunId(): ?string
    {
        return $this->runId;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getStatusMessage(): ?string
    {
        return $this->statusMessage;
    }

    /**
     * @return ModelTag[]|null
     */
    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function getRunLink(): ?string
    {
        return $this->runLink;
    }

    /**
     * @return array<string>|null
     */
    public function getAliases(): ?array
    {
        return $this->aliases;
    }

    // Status checks
    public function isReady(): bool
    {
        return $this->status === 'READY';
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['PENDING_REGISTRATION', 'PENDING']);
    }

    public function isFailed(): bool
    {
        return $this->status === 'FAILED_REGISTRATION';
    }

    // Stage checks
    public function isInProduction(): bool
    {
        return $this->currentStage === 'Production';
    }

    public function isInStaging(): bool
    {
        return $this->currentStage === 'Staging';
    }

    public function isArchived(): bool
    {
        return $this->currentStage === 'Archived';
    }
}