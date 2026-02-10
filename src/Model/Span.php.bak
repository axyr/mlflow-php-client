<?php

declare(strict_types=1);

namespace MLflow\Model;

use MLflow\Enum\SpanStatusCode;

class Span
{
    /**
     * @param string $traceId 32-character hex string
     * @param string $spanId 16-character hex string
     * @param string $name Span name
     * @param int $startTimeNs Start time in nanoseconds
     * @param int|null $endTimeNs End time in nanoseconds
     * @param string|null $parentId Parent span ID (16-char hex, null for root)
     * @param SpanStatusCode $status Status code
     * @param string $spanType Span type (uses string, not enum for extensibility)
     * @param mixed $inputs Input data (serializable)
     * @param mixed $outputs Output data (serializable)
     * @param array<string, mixed> $attributes Span attributes
     * @param SpanEvent[] $events Span events
     */
    public function __construct(
        private string $traceId,
        private string $spanId,
        private string $name,
        private int $startTimeNs,
        private ?int $endTimeNs,
        private ?string $parentId,
        private SpanStatusCode $status,
        private string $spanType,
        private mixed $inputs = null,
        private mixed $outputs = null,
        private array $attributes = [],
        private array $events = []
    ) {
    }

    public function getTraceId(): string
    {
        return $this->traceId;
    }

    public function getSpanId(): string
    {
        return $this->spanId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStartTimeNs(): int
    {
        return $this->startTimeNs;
    }

    public function getEndTimeNs(): ?int
    {
        return $this->endTimeNs;
    }

    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    public function getStatus(): SpanStatusCode
    {
        return $this->status;
    }

    public function getSpanType(): string
    {
        return $this->spanType;
    }

    public function getInputs(): mixed
    {
        return $this->inputs;
    }

    public function getOutputs(): mixed
    {
        return $this->outputs;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return SpanEvent[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * @deprecated Use getTraceId()
     */
    public function getRequestId(): string
    {
        return $this->traceId;
    }

    public function isRoot(): bool
    {
        return $this->parentId === null;
    }

    public function getDurationNs(): ?int
    {
        if ($this->endTimeNs === null) {
            return null;
        }
        return $this->endTimeNs - $this->startTimeNs;
    }

    public function getDurationMs(): ?float
    {
        $durationNs = $this->getDurationNs();
        if ($durationNs === null) {
            return null;
        }
        return $durationNs / 1_000_000;
    }

    public static function fromArray(array $data): self
    {
        $events = [];
        if (isset($data['events'])) {
            foreach ($data['events'] as $eventData) {
                $events[] = SpanEvent::fromArray($eventData);
            }
        }

        return new self(
            traceId: $data['trace_id'] ?? $data['request_id'], // Support deprecated request_id
            spanId: $data['span_id'],
            name: $data['name'],
            startTimeNs: (int) $data['start_time_ns'],
            endTimeNs: isset($data['end_time_ns']) ? (int) $data['end_time_ns'] : null,
            parentId: $data['parent_id'] ?? null,
            status: SpanStatusCode::from($data['status']),
            spanType: $data['span_type'],
            inputs: $data['inputs'] ?? null,
            outputs: $data['outputs'] ?? null,
            attributes: $data['attributes'] ?? [],
            events: $events
        );
    }

    public function toArray(): array
    {
        $data = [
            'trace_id' => $this->traceId,
            'span_id' => $this->spanId,
            'name' => $this->name,
            'start_time_ns' => $this->startTimeNs,
            'status' => $this->status->value,
            'span_type' => $this->spanType,
            'attributes' => $this->attributes,
            'events' => array_map(fn($e) => $e->toArray(), $this->events),
        ];

        if ($this->endTimeNs !== null) {
            $data['end_time_ns'] = $this->endTimeNs;
        }

        if ($this->parentId !== null) {
            $data['parent_id'] = $this->parentId;
        }

        if ($this->inputs !== null) {
            $data['inputs'] = $this->inputs;
        }

        if ($this->outputs !== null) {
            $data['outputs'] = $this->outputs;
        }

        return $data;
    }
}
