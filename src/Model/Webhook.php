<?php

declare(strict_types=1);

namespace MLflow\Model;

use MLflow\Contract\SerializableModelInterface;
use MLflow\Enum\WebhookStatus;

/**
 * Represents an MLflow Webhook
 */
readonly class Webhook implements SerializableModelInterface
{
    /**
     * @param string        $id             Webhook ID
     * @param string        $name           Webhook name
     * @param string        $url            Webhook URL
     * @param array<string> $events         List of events that trigger the webhook
     * @param WebhookStatus $status         Webhook status
     * @param string|null   $description    Optional description
     * @param int|null      $creationTime   Creation timestamp
     * @param int|null      $lastUpdateTime Last update timestamp
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $url,
        public array $events,
        public WebhookStatus $status,
        public ?string $description = null,
        public ?int $creationTime = null,
        public ?int $lastUpdateTime = null,
    ) {}

    /**
     * Create Webhook from array data
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $id = $data['id'] ?? '';
        $name = $data['name'] ?? '';
        $url = $data['url'] ?? '';
        $events = $data['events'] ?? [];
        if (is_array($events)) {
            /** @var array<string> $events */
        } else {
            $events = [];
        }
        $description = $data['description'] ?? null;
        $statusStr = $data['status'] ?? 'ACTIVE';
        $status = WebhookStatus::from(is_string($statusStr) ? $statusStr : 'ACTIVE');
        $creationTime = $data['creation_time'] ?? null;
        $lastUpdateTime = $data['last_update_time'] ?? null;

        if (is_int($creationTime)) {
            $creationTs = $creationTime;
        } elseif (is_numeric($creationTime)) {
            $creationTs = (int) $creationTime;
        } else {
            $creationTs = null;
        }

        if (is_int($lastUpdateTime)) {
            $lastUpdateTs = $lastUpdateTime;
        } elseif (is_numeric($lastUpdateTime)) {
            $lastUpdateTs = (int) $lastUpdateTime;
        } else {
            $lastUpdateTs = null;
        }

        return new self(
            id: is_string($id) ? $id : '',
            name: is_string($name) ? $name : '',
            url: is_string($url) ? $url : '',
            events: $events,
            status: $status,
            description: is_string($description) ? $description : null,
            creationTime: $creationTs,
            lastUpdateTime: $lastUpdateTs,
        );
    }

    /**
     * Convert to array representation
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'url' => $this->url,
            'events' => $this->events,
            'status' => $this->status->value,
        ];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->creationTime !== null) {
            $data['creation_time'] = $this->creationTime;
        }

        if ($this->lastUpdateTime !== null) {
            $data['last_update_time'] = $this->lastUpdateTime;
        }

        return $data;
    }

    /**
     * JSON serialization
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Check if webhook is active
     */
    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    // Deprecated getter methods for backward compatibility

    /**
     * @deprecated Use public property $id instead
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @deprecated Use public property $name instead
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @deprecated Use public property $url instead
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @deprecated Use public property $events instead
     *
     * @return array<string>
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * @deprecated Use public property $description instead
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @deprecated Use public property $status instead. Note: Returns enum now, not string
     */
    public function getStatus(): WebhookStatus
    {
        return $this->status;
    }

    /**
     * @deprecated Use public property $creationTime instead
     */
    public function getCreationTime(): ?int
    {
        return $this->creationTime;
    }

    /**
     * @deprecated Use public property $lastUpdateTime instead
     */
    public function getLastUpdateTime(): ?int
    {
        return $this->lastUpdateTime;
    }
}
