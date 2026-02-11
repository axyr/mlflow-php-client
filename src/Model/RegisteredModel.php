<?php

declare(strict_types=1);

namespace MLflow\Model;

use MLflow\Collection\TagCollection;

/**
 * Represents a registered model in MLflow Model Registry
 */
readonly class RegisteredModel
{
    public string $name;
    public ?string $description;
    public ?int $creationTimestamp;
    public ?int $lastUpdatedTimestamp;
    /** @var array<ModelVersion>|null */
    public ?array $latestVersions;
    /** @var TagCollection<ModelTag>|null */
    public ?TagCollection $tags;
    /** @var array<ModelAlias>|null */
    public ?array $aliases;

    /**
     * @param string $name
     * @param string|null $description
     * @param int|null $creationTimestamp
     * @param int|null $lastUpdatedTimestamp
     * @param array<ModelVersion>|null $latestVersions
     * @param TagCollection<ModelTag>|null $tags
     * @param array<ModelAlias>|null $aliases
     */
    public function __construct(
        string $name,
        ?string $description = null,
        ?int $creationTimestamp = null,
        ?int $lastUpdatedTimestamp = null,
        ?array $latestVersions = null,
        ?TagCollection $tags = null,
        ?array $aliases = null
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->creationTimestamp = $creationTimestamp;
        $this->lastUpdatedTimestamp = $lastUpdatedTimestamp;
        $this->latestVersions = $latestVersions;
        $this->tags = $tags;
        $this->aliases = $aliases;
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $latestVersions = null;
        if (isset($data['latest_versions']) && is_array($data['latest_versions'])) {
            $latestVersions = [];
            foreach ($data['latest_versions'] as $versionData) {
                if (is_array($versionData)) {
                    $latestVersions[] = ModelVersion::fromArray($versionData);
                }
            }
        }

        $tags = null;
        if (isset($data['tags']) && is_array($data['tags'])) {
            $tagCollection = new TagCollection();
            foreach ($data['tags'] as $tagData) {
                if (is_array($tagData)) {
                    /** @phpstan-ignore-next-line Array shape validated */
                    $tagCollection->add(ModelTag::fromArray($tagData));
                }
            }
            $tags = $tagCollection;
        }

        $aliases = null;
        if (isset($data['aliases']) && is_array($data['aliases'])) {
            $aliases = [];
            foreach ($data['aliases'] as $aliasData) {
                if (is_array($aliasData)) {
                    /** @phpstan-ignore-next-line Array shape validated */
                    $aliases[] = ModelAlias::fromArray($aliasData);
                }
            }
        }

        $description = $data['description'] ?? null;
        $name = $data['name'] ?? '';
        $creationTimestamp = $data['creation_timestamp'] ?? null;
        $lastUpdatedTimestamp = $data['last_updated_timestamp'] ?? null;

        return new self(
            is_string($name) ? $name : '',
            is_string($description) ? $description : null,
            is_int($creationTimestamp) ? $creationTimestamp : (is_numeric($creationTimestamp) ? (int) $creationTimestamp : null),
            is_int($lastUpdatedTimestamp) ? $lastUpdatedTimestamp : (is_numeric($lastUpdatedTimestamp) ? (int) $lastUpdatedTimestamp : null),
            $latestVersions,
            $tags,
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
        ];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->creationTimestamp !== null) {
            $data['creation_timestamp'] = $this->creationTimestamp;
        }

        if ($this->lastUpdatedTimestamp !== null) {
            $data['last_updated_timestamp'] = $this->lastUpdatedTimestamp;
        }

        if ($this->latestVersions !== null) {
            $data['latest_versions'] = array_map(fn($v) => $v->toArray(), $this->latestVersions);
        }

        if ($this->tags !== null) {
            $data['tags'] = $this->tags->toArray();
        }

        if ($this->aliases !== null) {
            $data['aliases'] = array_map(fn($a) => $a->toArray(), $this->aliases);
        }

        return $data;
    }

    // Getters
    /** @deprecated Access $name property directly */
    public function getName(): string
    {
        return $this->name;
    }

    /** @deprecated Access $description property directly */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /** @deprecated Access $creationTimestamp property directly */
    public function getCreationTimestamp(): ?int
    {
        return $this->creationTimestamp;
    }

    /** @deprecated Access $lastUpdatedTimestamp property directly */
    public function getLastUpdatedTimestamp(): ?int
    {
        return $this->lastUpdatedTimestamp;
    }

    /**
     * @return ModelVersion[]|null
     * @deprecated Access $latestVersions property directly
     */
    public function getLatestVersions(): ?array
    {
        return $this->latestVersions;
    }

    /**
     * @return ModelTag[]|null
     * @deprecated Access $tags property directly
     */
    public function getTags(): ?array
    {
        return $this->tags?->all();
    }

    /**
     * @return array<ModelAlias>|null
     * @deprecated Access $aliases property directly
     */
    public function getAliases(): ?array
    {
        return $this->aliases;
    }

    /**
     * Get latest version for a specific stage
     */
    public function getLatestVersionForStage(string $stage): ?ModelVersion
    {
        if ($this->latestVersions === null) {
            return null;
        }

        foreach ($this->latestVersions as $version) {
            if ($version->currentStage?->value === $stage) {
                return $version;
            }
        }

        return null;
    }
}
