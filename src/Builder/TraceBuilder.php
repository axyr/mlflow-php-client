<?php

declare(strict_types=1);

namespace MLflow\Builder;

use MLflow\Model\Trace;
use MLflow\Model\TraceInfo;
use MLflow\Model\TraceData;
use MLflow\Model\MlflowExperimentLocation;
use MLflow\Model\Span;
use MLflow\Enum\TraceState;
use MLflow\Enum\SpanType;
use MLflow\Util\TraceIdGenerator;
use MLflow\Util\TimestampHelper;

class TraceBuilder
{
    private string $traceId;
    private string $experimentId;
    /** @phpstan-ignore-next-line (Reserved for future use) */
    private string $name;
    private int $startTimeNs;

    /** @var Span[] */
    private array $spans = [];

    /** @var array<string, string> */
    private array $tags = [];

    /** @phpstan-ignore-next-line (Reserved for future use) */
    private ?string $rootSpanId = null;

    public function __construct(string $experimentId, string $name)
    {
        $this->traceId = TraceIdGenerator::generateTraceId();
        $this->experimentId = $experimentId;
        $this->name = $name;
        $this->startTimeNs = TimestampHelper::nowNs();
    }

    public function getTraceId(): string
    {
        return $this->traceId;
    }

    public function withTag(string $key, string $value): self
    {
        $this->tags[$key] = $value;
        return $this;
    }

    /**
     * Start a new span
     *
     * @param string $name Span name
     * @param string $spanType Span type
     * @param array<string, mixed>|null $inputs Span inputs
     * @param array<string, mixed>|null $attributes Span attributes
     * @return SpanBuilder
     */
    public function startSpan(
        string $name,
        string $spanType = SpanType::UNKNOWN,
        ?array $inputs = null,
        ?array $attributes = null
    ): SpanBuilder {
        return new SpanBuilder($this, $name, $spanType, $inputs, $attributes);
    }

    /**
     * @internal Called by SpanBuilder
     */
    public function addSpan(Span $span): void
    {
        if ($span->getParentId() === null) {
            $this->rootSpanId = $span->getSpanId();
        }
        $this->spans[] = $span;
    }

    public function build(): Trace
    {
        $endTimeNs = TimestampHelper::nowNs();
        $executionDurationMs = TimestampHelper::nsToMs($endTimeNs - $this->startTimeNs);

        // Determine state from spans
        $state = TraceState::OK;
        foreach ($this->spans as $span) {
            if ($span->getStatus()->isError()) {
                $state = TraceState::ERROR;
                break;
            }
        }

        $info = new TraceInfo(
            traceId: $this->traceId,
            traceLocation: new MlflowExperimentLocation($this->experimentId),
            requestTime: TimestampHelper::nowMs(),
            state: $state,
            executionDuration: $executionDurationMs,
            tags: $this->tags
        );

        $data = new TraceData($this->spans);

        return new Trace($info, $data);
    }
}
