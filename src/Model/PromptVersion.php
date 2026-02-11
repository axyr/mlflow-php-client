<?php

declare(strict_types=1);

namespace MLflow\Model;

/**
 * Represents an MLflow Prompt Version
 */
class PromptVersion
{
    private string $name;

    private string $version;

    private string $template;

    private ?string $description;

    /** @var array<string, string>|null */
    private ?array $tags;

    /** @var array<string, mixed>|null */
    private ?array $responseFormat;

    /** @var array<string, mixed>|null */
    private ?array $modelConfig;

    private ?int $creationTime;

    private ?int $lastUpdateTime;

    private ?string $userId;

    /**
     * @param array<string, string>|null $tags
     * @param array<string, mixed>|null  $responseFormat
     * @param array<string, mixed>|null  $modelConfig
     */
    public function __construct(
        string $name,
        string $version,
        string $template,
        ?string $description = null,
        ?array $tags = null,
        ?array $responseFormat = null,
        ?array $modelConfig = null,
        ?int $creationTime = null,
        ?int $lastUpdateTime = null,
        ?string $userId = null
    ) {
        $this->name = $name;
        $this->version = $version;
        $this->template = $template;
        $this->description = $description;
        $this->tags = $tags;
        $this->responseFormat = $responseFormat;
        $this->modelConfig = $modelConfig;
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
        $version = $data['version'] ?? '';
        $template = $data['template'] ?? '';
        $description = $data['description'] ?? null;
        $tags = $data['tags'] ?? null;
        if (is_array($tags)) {
            /** @var array<string, string> $tags */
        } else {
            $tags = null;
        }
        $responseFormat = $data['response_format'] ?? null;
        if (is_array($responseFormat)) {
            /** @var array<string, mixed> $responseFormat */
        } else {
            $responseFormat = null;
        }
        $modelConfig = $data['model_config'] ?? null;
        if (is_array($modelConfig)) {
            /** @var array<string, mixed> $modelConfig */
        } else {
            $modelConfig = null;
        }
        $creationTime = $data['creation_time'] ?? null;
        $lastUpdateTime = $data['last_update_time'] ?? null;
        $userId = $data['user_id'] ?? null;

        return new self(
            is_string($name) ? $name : '',
            is_string($version) ? $version : (is_int($version) ? (string) $version : ''),
            is_string($template) ? $template : '',
            is_string($description) ? $description : null,
            $tags,
            $responseFormat,
            $modelConfig,
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
        $data = [
            'name' => $this->name,
            'version' => $this->version,
            'template' => $this->template,
        ];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->tags !== null) {
            $data['tags'] = $this->tags;
        }

        if ($this->responseFormat !== null) {
            $data['response_format'] = $this->responseFormat;
        }

        if ($this->modelConfig !== null) {
            $data['model_config'] = $this->modelConfig;
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

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getTemplate(): string
    {
        return $this->template;
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

    /**
     * @return array<string, mixed>|null
     */
    public function getResponseFormat(): ?array
    {
        return $this->responseFormat;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getModelConfig(): ?array
    {
        return $this->modelConfig;
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
