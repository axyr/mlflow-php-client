<?php

declare(strict_types=1);

namespace MLflow\Model;

/**
 * Represents an MLflow Prompt
 */
class Prompt
{
    private string $name;

    private ?string $description;

    /** @var array<string, string>|null */
    private ?array $tags;

    private ?int $creationTime;

    private ?int $lastUpdateTime;

    private ?string $userId;

    /**
     * @param array<string, string>|null $tags
     */
    public function __construct(
        string $name,
        ?string $description = null,
        ?array $tags = null,
        ?int $creationTime = null,
        ?int $lastUpdateTime = null,
        ?string $userId = null
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->tags = $tags;
        $this->creationTime = $creationTime;
        $this->lastUpdateTime = $lastUpdateTime;
        $this->userId = $userId;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $name = $data['name'] ?? '';
        $description = $data['description'] ?? null;
        $tags = $data['tags'] ?? null;
        if (is_array($tags)) {
            /** @var array<string, string> $tags */
        } else {
            $tags = null;
        }
        $creationTime = $data['creation_time'] ?? null;
        $lastUpdateTime = $data['last_update_time'] ?? null;
        $userId = $data['user_id'] ?? null;

        return new self(
            is_string($name) ? $name : '',
            is_string($description) ? $description : null,
            $tags,
            is_int($creationTime) ? $creationTime : (is_numeric($creationTime) ? (int) $creationTime : null),
            is_int($lastUpdateTime) ? $lastUpdateTime : (is_numeric($lastUpdateTime) ? (int) $lastUpdateTime : null),
            is_string($userId) ? $userId : null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = ['name' => $this->name];

        if ($this->description !== null) {
            $data['description'] = $this->description;
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

        if ($this->userId !== null) {
            $data['user_id'] = $this->userId;
        }

        return $data;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return array<string, string>|null
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

    public function getUserId(): ?string
    {
        return $this->userId;
    }
}
