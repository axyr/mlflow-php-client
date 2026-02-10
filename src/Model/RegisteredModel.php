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
    /** @var array<ModelAlias>|null */
    private ?array $aliases;

    /**
     * @param string $name
     * @param string|null $description
     * @param int|null $creationTimestamp
     * @param int|null $lastUpdatedTimestamp
     * @param array<ModelVersion>|null $latestVersions
     * @param array<ModelTag>|null $tags
     * @param array<ModelAlias>|null $aliases
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
            $tags = [];
            foreach ($data['tags'] as $tagData) {
                if (is_array($tagData)) {
                    /** @phpstan-ignore-next-line Array shape validated */
                    $tags[] = ModelTag::fromArray($tagData);
                }
            }
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
     * @return array<ModelAlias>|null
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