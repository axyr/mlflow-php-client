<?php

declare(strict_types=1);

namespace MLflow\Model;

class SpanEvent
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        private string $name,
        private int $timestampNs,
        private array $attributes = []
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTimestampNs(): int
    {
        return $this->timestampNs;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public static function exception(\Throwable $e, int $timestampNs): self
    {
        return new self(
            name: 'exception',
            timestampNs: $timestampNs,
            attributes: [
                'exception.type' => get_class($e),
                'exception.message' => $e->getMessage(),
                'exception.stacktrace' => $e->getTraceAsString(),
            ]
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            timestampNs: (int) $data['timestamp_ns'],
            attributes: $data['attributes'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'timestamp_ns' => $this->timestampNs,
            'attributes' => $this->attributes,
        ];
    }
}
