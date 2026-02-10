<?php

declare(strict_types=1);

namespace MLflow\Model;

/**
 * Represents an MLflow Webhook
 */
class Webhook
{
    private string $id;
    private string $name;
    private string $url;
    /** @var array<string> */
    private array $events;
    private ?string $description;
    private ?string $status;
    private ?int $creationTime;
    private ?int $lastUpdateTime;

    /**
     * @param string $id
     * @param string $name
     * @param string $url
     * @param array<string> $events
     * @param string|null $description
     * @param string|null $status
     * @param int|null $creationTime
     * @param int|null $lastUpdateTime
     */
    public function __construct(
        string $id,
        string $name,
        string $url,
        array $events,
        ?string $description = null,
        ?string $status = null,
        ?int $creationTime = null,
        ?int $lastUpdateTime = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->url = $url;
        $this->events = $events;
        $this->description = $description;
        $this->status = $status;
        $this->creationTime = $creationTime;
        $this->lastUpdateTime = $lastUpdateTime;
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $id = $data['id'] ?? '';
        $name = $data['name'] ?? '';
        $url = $data['url'] ?? '';
        $events = $data['events'] ?? [];
        $description = $data['description'] ?? null;
        $status = $data['status'] ?? null;
        $creationTime = $data['creation_time'] ?? null;
        $lastUpdateTime = $data['last_update_time'] ?? null;

        return new self(
            is_string($id) ? $id : '',
            is_string($name) ? $name : '',
            is_string($url) ? $url : '',
            is_array($events) ? $events : [],
            is_string($description) ? $description : null,
            is_string($status) ? $status : null,
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
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'events' => $this->events,
        ];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->status !== null) {
            $data['status'] = $this->status;
        }

        if ($this->creationTime !== null) {
            $data['creation_time'] = $this->creationTime;
        }

        if ($this->lastUpdateTime !== null) {
            $data['last_update_time'] = $this->lastUpdateTime;
        }

        return $data;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return array<string>
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): ?string
    {
        return $this->status;
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
        return $this->status === 'ACTIVE';
    }
}
