<?php

declare(strict_types=1);

namespace MLflow\Builder;

use MLflow\Model\Span;
use MLflow\Model\SpanEvent;
use MLflow\Enum\SpanStatusCode;
use MLflow\Util\TraceIdGenerator;
use MLflow\Util\TimestampHelper;

class SpanBuilder
{
    private TraceBuilder $traceBuilder;
    private string $spanId;
    private string $name;
    private string $spanType;
    private int $startTimeNs;
    private ?int $endTimeNs = null;
    private ?string $parentId = null;
    private mixed $inputs;
    private mixed $outputs = null;

    /** @var array<string, mixed> */
    private array $attributes;

    /** @var SpanEvent[] */
    private array $events = [];

    private SpanStatusCode $status = SpanStatusCode::UNSET;

    public function __construct(
        TraceBuilder $traceBuilder,
        string $name,
        string $spanType,
        ?array $inputs,
        ?array $attributes
    ) {
        $this->traceBuilder = $traceBuilder;
        $this->spanId = TraceIdGenerator::generateSpanId();
        $this->name = $name;
        $this->spanType = $spanType;
        $this->inputs = $inputs;
        $this->attributes = $attributes ?? [];
        $this->startTimeNs = TimestampHelper::nowNs();
    }

    public function withParent(string $parentSpanId): self
    {
        $this->parentId = $parentSpanId;
        return $this;
    }

    public function withInput(string $key, mixed $value): self
    {
        if (!is_array($this->inputs)) {
            $this->inputs = [];
        }
        $this->inputs[$key] = $value;
        return $this;
    }

    public function withOutput(string $key, mixed $value): self
    {
        if (!is_array($this->outputs)) {
            $this->outputs = [];
        }
        $this->outputs[$key] = $value;
        return $this;
    }

    public function withAttribute(string $key, mixed $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    public function withEvent(string $name, array $attributes = []): self
    {
        $this->events[] = new SpanEvent(
            $name,
            TimestampHelper::nowNs(),
            $attributes
        );
        return $this;
    }

    public function withError(\Throwable $exception): self
    {
        $this->status = SpanStatusCode::ERROR;
        $this->events[] = SpanEvent::exception($exception, TimestampHelper::nowNs());
        return $this;
    }

    public function end(?SpanStatusCode $status = null): TraceBuilder
    {
        $this->endTimeNs = TimestampHelper::nowNs();

        if ($status !== null) {
            $this->status = $status;
        } elseif ($this->status === SpanStatusCode::UNSET) {
            $this->status = SpanStatusCode::OK;
        }

        $span = new Span(
            traceId: $this->traceBuilder->getTraceId(),
            spanId: $this->spanId,
            name: $this->name,
            startTimeNs: $this->startTimeNs,
            endTimeNs: $this->endTimeNs,
            parentId: $this->parentId,
            status: $this->status,
            spanType: $this->spanType,
            inputs: $this->inputs,
            outputs: $this->outputs,
            attributes: $this->attributes,
            events: $this->events
        );

        $this->traceBuilder->addSpan($span);

        return $this->traceBuilder;
    }
}
