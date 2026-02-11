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
    ) {}

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

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $name = $data['name'] ?? '';
        $timestampNs = $data['timestamp_ns'] ?? 0;
        $attributes = $data['attributes'] ?? [];

        return new self(
            name: is_string($name) ? $name : '',
            timestampNs: is_int($timestampNs) ? $timestampNs : (is_numeric($timestampNs) ? (int) $timestampNs : 0),
            attributes: is_array($attributes) ? $attributes : []
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'timestamp_ns' => $this->timestampNs,
            'attributes' => $this->attributes,
        ];
    }
}
