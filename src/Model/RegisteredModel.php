<?php

declare(strict_types=1);

namespace MLflow\Model;

/**
 * Represents a registered model in MLflow Model Registry
 */
class RegisteredModel
{
    private string $name;
    private ?string $description;
    private ?int $creationTimestamp;
    private ?int $lastUpdatedTimestamp;
    /** @var array<ModelVersion>|null */
    private ?array $latestVersions;
    /** @var array<ModelTag>|null */
    private ?array $tags;
    /** @var array<string>|null */
    private ?array $aliases;

    /**
     * @param string $name
     * @param string|null $description
     * @param int|null $creationTimestamp
     * @param int|null $lastUpdatedTimestamp
     * @param array<ModelVersion>|null $latestVersions
     * @param array<ModelTag>|null $tags
     * @param array<string>|null $aliases
     */
    public function __construct(
        string $name,
        ?string $description = null,
        ?int $creationTimestamp = null,
        ?int $lastUpdatedTimestamp = null,
        ?array $latestVersions = null,
        ?array $tags = null,
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
        if (isset($data['latest_versions'])) {
            $latestVersions = [];
            foreach ($data['latest_versions'] as $versionData) {
                $latestVersions[] = ModelVersion::fromArray($versionData);
            }
        }

        $tags = null;
        if (isset($data['tags'])) {
            $tags = [];
            foreach ($data['tags'] as $tagData) {
                $tags[] = ModelTag::fromArray($tagData);
            }
        }

        $aliases = null;
        if (isset($data['aliases'])) {
            $aliases = [];
            foreach ($data['aliases'] as $aliasData) {
                $aliases[] = ModelAlias::fromArray($aliasData);
            }
        }

        return new self(
            $data['name'],
            $data['description'] ?? null,
            isset($data['creation_timestamp']) ? (int) $data['creation_timestamp'] : null,
            isset($data['last_updated_timestamp']) ? (int) $data['last_updated_timestamp'] : null,
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
            $data['tags'] = array_map(fn($t) => $t->toArray(), $this->tags);
        }

        if ($this->aliases !== null) {
            $data['aliases'] = array_map(fn($a) => $a->toArray(), $this->aliases);
        }

        return $data;
    }

    // Getters
    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCreationTimestamp(): ?int
    {
        return $this->creationTimestamp;
    }

    public function getLastUpdatedTimestamp(): ?int
    {
        return $this->lastUpdatedTimestamp;
    }

    /**
     * @return ModelVersion[]|null
     */
    public function getLatestVersions(): ?array
    {
        return $this->latestVersions;
    }

    /**
     * @return ModelTag[]|null
     */
    public function getTags(): ?array
    {
        return $this->tags;
    }

    /**
     * @return ModelAlias[]|null
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
            if ($version->getCurrentStage() === $stage) {
                return $version;
            }
        }

        return null;
    }
}