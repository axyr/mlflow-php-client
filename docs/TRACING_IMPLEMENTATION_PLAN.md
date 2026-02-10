# MLflow Tracing Implementation Plan (Updated from Source Code Analysis)

> **Last Updated**: 2026-02-10
> **Based on**: MLflow Python SDK source code analysis (v2.14.0+)

## Overview

This document outlines the implementation plan for adding MLflow Tracing functionality to the MLflow PHP Client, based on comprehensive analysis of the official MLflow Python SDK source code.

---

## 1. Source Code Analysis Summary

### 1.1 Key Findings

**Reviewed Files:**
- `mlflow/tracking/client.py` - Client API methods
- `mlflow/store/tracking/rest_store.py` - REST API calls
- `mlflow/entities/trace.py`, `trace_info.py`, `trace_data.py`, `span.py` - Data models
- `mlflow/tracing/fluent.py` - Fluent API implementation
- `mlflow/tracing/utils/__init__.py` - ID encoding utilities

**Critical Discoveries:**
1. **Timing**: Spans use **nanoseconds**, TraceInfo uses **milliseconds**
2. **IDs**: Hex strings - Trace ID (32-char), Span ID (16-char)
3. **API Versions**: V2 (deprecated), V3 (current), V4 (future)
4. **In-Memory Management**: Traces built in-memory, sent when root span ends
5. **OpenTelemetry Integration**: Uses OpenTelemetry span format

---

## 2. Architecture Overview

### 2.1 Data Flow (Actual Implementation)

```
Application Code
    ↓
InMemoryTraceManager (holds active traces/spans)
    ↓
LiveSpan (OpenTelemetry-based span)
    ↓
RestStore (when root span ends)
    ↓
POST /api/3.0/mlflow/traces (V3 endpoint)
```

### 2.2 Core Components

1. **Models** - Trace, TraceInfo, TraceData, Span (OpenTelemetry-compatible)
2. **API Client** - TraceApi for REST calls
3. **Builders** - Optional fluent API (PHP doesn't have in-memory managers like Python)
4. **Enums** - SpanType, SpanStatusCode, TraceState
5. **Utilities** - ID generation (hex strings), timestamp conversion

---

## 3. Phase 1: Core Data Models (Based on Actual Source)

### 3.1 TraceInfo (from `mlflow/entities/trace_info.py`)

#### `src/Model/TraceInfo.php`
```php
<?php

namespace MLflow\Model;

use MLflow\Enum\TraceState;

class TraceInfo
{
    /**
     * @param string $traceId 32-character hex string
     * @param TraceLocation $traceLocation Experiment or inference table
     * @param int $requestTime Milliseconds since epoch
     * @param TraceState $state OK, ERROR, IN_PROGRESS
     * @param string|null $requestPreview Preview of request
     * @param string|null $responsePreview Preview of response
     * @param string|null $clientRequestId Client-provided request ID
     * @param int|null $executionDuration Duration in milliseconds
     * @param array<string, string> $traceMetadata Metadata key-value pairs
     * @param array<string, string> $tags Tags key-value pairs
     * @param Assessment[] $assessments Optional assessments
     */
    public function __construct(
        private string $traceId,
        private TraceLocation $traceLocation,
        private int $requestTime,
        private TraceState $state,
        private ?string $requestPreview = null,
        private ?string $responsePreview = null,
        private ?string $clientRequestId = null,
        private ?int $executionDuration = null,
        private array $traceMetadata = [],
        private array $tags = [],
        private array $assessments = []
    ) {}

    // Getters...
    public function getTraceId(): string;
    public function getRequestTime(): int; // milliseconds
    public function getState(): TraceState;
    public function getExecutionDuration(): ?int; // milliseconds

    /**
     * @deprecated Use getTraceId()
     */
    public function getRequestId(): string;

    public static function fromArray(array $data): self;
    public function toArray(): array;
}
```

### 3.2 TraceLocation

#### `src/Model/TraceLocation.php`
```php
<?php

namespace MLflow\Model;

abstract class TraceLocation
{
    abstract public function toArray(): array;
    abstract public static function fromArray(array $data): self;
}

class MlflowExperimentLocation extends TraceLocation
{
    public function __construct(private string $experimentId) {}

    public function getExperimentId(): string;
}

class InferenceTableLocation extends TraceLocation
{
    public function __construct(
        private string $tableName,
        private string $database,
        private string $catalog
    ) {}
}
```

### 3.3 Trace (from `mlflow/entities/trace.py`)

#### `src/Model/Trace.php`
```php
<?php

namespace MLflow\Model;

class Trace
{
    public function __construct(
        private TraceInfo $info,
        private TraceData $data
    ) {}

    public function getInfo(): TraceInfo;
    public function getData(): TraceData;

    public static function fromArray(array $data): self;
    public function toArray(): array;
    public function toJson(): string;
}
```

### 3.4 TraceData (from `mlflow/entities/trace_data.py`)

#### `src/Model/TraceData.php`
```php
<?php

namespace MLflow\Model;

class TraceData
{
    /**
     * @param Span[] $spans
     */
    public function __construct(private array $spans = []) {}

    /**
     * @return Span[]
     */
    public function getSpans(): array;

    public function getRootSpan(): ?Span;

    public static function fromArray(array $data): self;
    public function toArray(): array;
}
```

### 3.5 Span (from `mlflow/entities/span.py`)

#### `src/Model/Span.php`
```php
<?php

namespace MLflow\Model;

use MLflow\Enum\SpanType;
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
    ) {}

    public function getTraceId(): string;
    public function getSpanId(): string;
    public function getName(): string;
    public function getStartTimeNs(): int;
    public function getEndTimeNs(): ?int;
    public function getParentId(): ?string;
    public function getStatus(): SpanStatusCode;
    public function getSpanType(): string;

    /**
     * @deprecated Use getTraceId()
     */
    public function getRequestId(): string;

    public function isRoot(): bool;
    public function getDurationNs(): ?int;
    public function getDurationMs(): ?float;

    public static function fromArray(array $data): self;
    public function toArray(): array;
}
```

### 3.6 SpanEvent

#### `src/Model/SpanEvent.php`
```php
<?php

namespace MLflow\Model;

class SpanEvent
{
    public function __construct(
        private string $name,
        private int $timestampNs,
        private array $attributes = []
    ) {}

    public static function exception(\Throwable $e, int $timestampNs): self;

    public static function fromArray(array $data): self;
    public function toArray(): array;
}
```

---

## 4. Phase 2: Enums (From Actual Source)

### 4.1 SpanType (from `mlflow/entities/span.py`)

#### `src/Enum/SpanType.php`
```php
<?php

namespace MLflow\Enum;

/**
 * Note: MLflow uses string constants, not enums, to allow custom types
 * We use a class with constants for the same flexibility
 */
class SpanType
{
    public const UNKNOWN = 'UNKNOWN';
    public const AGENT = 'AGENT';
    public const CHAIN = 'CHAIN';
    public const LLM = 'LLM';
    public const TOOL = 'TOOL';
    public const RETRIEVER = 'RETRIEVER';
    public const EMBEDDING = 'EMBEDDING';
    public const PARSER = 'PARSER';
    public const RERANKER = 'RERANKER';
    public const CHAT_MODEL = 'CHAT_MODEL';
    public const MEMORY = 'MEMORY';
    public const WORKFLOW = 'WORKFLOW';
    public const TASK = 'TASK';
    public const GUARDRAIL = 'GUARDRAIL';
    public const EVALUATOR = 'EVALUATOR';

    public static function isValid(string $type): bool
    {
        $reflection = new \ReflectionClass(self::class);
        return in_array($type, $reflection->getConstants(), true);
    }

    public static function isLLMRelated(string $type): bool
    {
        return in_array($type, [
            self::LLM,
            self::CHAT_MODEL,
            self::EMBEDDING,
        ], true);
    }
}
```

### 4.2 SpanStatusCode (from `mlflow/entities/span_status.py`)

#### `src/Enum/SpanStatusCode.php`
```php
<?php

namespace MLflow\Enum;

enum SpanStatusCode: string
{
    case UNSET = 'UNSET';
    case OK = 'OK';
    case ERROR = 'ERROR';

    public function isError(): bool
    {
        return $this === self::ERROR;
    }

    public function isOk(): bool
    {
        return $this === self::OK;
    }
}
```

### 4.3 TraceState

#### `src/Enum/TraceState.php`
```php
<?php

namespace MLflow\Enum;

enum TraceState: string
{
    case IN_PROGRESS = 'IN_PROGRESS';
    case OK = 'OK';
    case ERROR = 'ERROR';

    public function isTerminal(): bool
    {
        return $this !== self::IN_PROGRESS;
    }
}
```

---

## 5. Phase 3: Utilities (From Actual Implementation)

### 5.1 ID Generation (from `mlflow/tracing/utils/__init__.py`)

#### `src/Util/TraceIdGenerator.php`
```php
<?php

namespace MLflow\Util;

class TraceIdGenerator
{
    /**
     * Generate a trace ID compatible with OpenTelemetry
     * Returns a 32-character hexadecimal string (128-bit)
     */
    public static function generateTraceId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Generate a span ID compatible with OpenTelemetry
     * Returns a 16-character hexadecimal string (64-bit)
     */
    public static function generateSpanId(): string
    {
        return bin2hex(random_bytes(8));
    }

    /**
     * Decode a hex ID string to integer
     */
    public static function decodeId(string $hexId): int|string
    {
        // For IDs > PHP_INT_MAX, return as string
        if (strlen($hexId) > 14) {
            return $hexId; // Keep as hex string
        }
        return hexdec($hexId);
    }

    /**
     * Validate trace ID format
     */
    public static function isValidTraceId(string $traceId): bool
    {
        return preg_match('/^[0-9a-f]{32}$/i', $traceId) === 1;
    }

    /**
     * Validate span ID format
     */
    public static function isValidSpanId(string $spanId): bool
    {
        return preg_match('/^[0-9a-f]{16}$/i', $spanId) === 1;
    }
}
```

### 5.2 Timestamp Helper (Supports both ns and ms)

#### `src/Util/TimestampHelper.php`
```php
<?php

namespace MLflow\Util;

class TimestampHelper
{
    /**
     * Get current timestamp in nanoseconds (for spans)
     */
    public static function nowNs(): int
    {
        return (int) (microtime(true) * 1_000_000_000);
    }

    /**
     * Get current timestamp in milliseconds (for traces)
     */
    public static function nowMs(): int
    {
        return (int) (microtime(true) * 1_000);
    }

    /**
     * Convert milliseconds to nanoseconds
     */
    public static function msToNs(int $ms): int
    {
        return $ms * 1_000_000;
    }

    /**
     * Convert nanoseconds to milliseconds
     */
    public static function nsToMs(int $ns): int
    {
        return (int) ($ns / 1_000_000);
    }

    /**
     * Convert nanoseconds to DateTime
     */
    public static function nsToDateTime(int $ns): \DateTimeImmutable
    {
        $seconds = $ns / 1_000_000_000;
        return \DateTimeImmutable::createFromFormat('U.u', sprintf('%.9f', $seconds))
            ?: new \DateTimeImmutable();
    }

    /**
     * Convert DateTime to nanoseconds
     */
    public static function dateTimeToNs(\DateTimeInterface $dateTime): int
    {
        return (int) ($dateTime->getTimestamp() * 1_000_000_000);
    }
}
```

---

## 6. Phase 4: API Client (Based on rest_store.py)

### 6.1 TraceApi Endpoints

**From `mlflow/utils/rest_utils.py`:**
- V2: `/api/2.0/mlflow/traces` (deprecated)
- V3: `/api/3.0/mlflow/traces` (**current**, recommended)
- V4: `/api/4.0/mlflow/traces` (future)
- OTLP: `/v1/traces` (OpenTelemetry format)

### 6.2 TraceApi Implementation

#### `src/Api/TraceApi.php`
```php
<?php

namespace MLflow\Api;

use MLflow\Model\Trace;
use MLflow\Model\TraceInfo;
use MLflow\Enum\TraceState;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

class TraceApi extends BaseApi
{
    private const API_VERSION = '3.0'; // Use V3

    /**
     * Get trace by ID
     * Endpoint: GET /api/3.0/mlflow/traces/{trace_id}
     */
    public function getTrace(string $traceId): Trace
    {
        $response = $this->httpClient->request('POST', "mlflow/traces/get", [
            'json' => ['trace_id' => $traceId]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        return Trace::fromArray($data['trace']);
    }

    /**
     * Search traces
     * Endpoint: POST /api/3.0/mlflow/traces/search
     *
     * @param string[] $experimentIds
     * @param string[] $orderBy
     * @return array{traces: Trace[], next_page_token: string|null}
     */
    public function searchTraces(
        array $experimentIds,
        ?string $filterString = null,
        int $maxResults = 1000,
        ?array $orderBy = null,
        ?string $pageToken = null,
        ?string $runId = null,
        ?string $modelId = null
    ): array {
        $params = [
            'experiment_ids' => $experimentIds,
            'max_results' => $maxResults,
        ];

        if ($filterString) $params['filter'] = $filterString;
        if ($orderBy) $params['order_by'] = $orderBy;
        if ($pageToken) $params['page_token'] = $pageToken;
        if ($runId) $params['run_id'] = $runId;
        if ($modelId) $params['model_id'] = $modelId;

        $response = $this->httpClient->request('POST', 'mlflow/traces/search', [
            'json' => $params
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return [
            'traces' => array_map([Trace::class, 'fromArray'], $data['traces'] ?? []),
            'next_page_token' => $data['next_page_token'] ?? null,
        ];
    }

    /**
     * Log a complete trace (end the trace)
     * Endpoint: POST /api/3.0/mlflow/traces
     *
     * Note: In MLflow Python, this is called at the END of a trace
     */
    public function logTrace(Trace $trace): TraceInfo
    {
        $response = $this->httpClient->request('POST', 'mlflow/traces', [
            'json' => ['trace' => $trace->toArray()]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        return TraceInfo::fromArray($data['trace_info']);
    }

    /**
     * Delete traces
     * Endpoint: DELETE /api/3.0/mlflow/traces
     *
     * @param string[] $traceIds
     */
    public function deleteTraces(
        array $traceIds,
        string $experimentId,
        int $maxTraces = 100
    ): int {
        $response = $this->httpClient->request('DELETE', 'mlflow/traces', [
            'json' => [
                'experiment_id' => $experimentId,
                'trace_ids' => $traceIds,
                'max_traces' => $maxTraces,
            ]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        return $data['traces_deleted'] ?? 0;
    }

    /**
     * Set trace tag
     * Endpoint: POST /api/3.0/mlflow/traces/set-tag
     */
    public function setTraceTag(string $traceId, string $key, string $value): void
    {
        $this->httpClient->request('POST', 'mlflow/traces/set-tag', [
            'json' => [
                'trace_id' => $traceId,
                'key' => $key,
                'value' => $value,
            ]
        ]);
    }

    /**
     * Delete trace tag
     * Endpoint: DELETE /api/3.0/mlflow/traces/delete-tag
     */
    public function deleteTraceTag(string $traceId, string $key): void
    {
        $this->httpClient->request('DELETE', 'mlflow/traces/delete-tag', [
            'json' => [
                'trace_id' => $traceId,
                'key' => $key,
            ]
        ]);
    }
}
```

---

## 7. Phase 5: Builder API (Simplified for PHP)

**Note**: PHP doesn't have Python's in-memory trace manager or context managers. The builder creates complete traces that are then sent in one call.

### 7.1 TraceBuilder

#### `src/Builder/TraceBuilder.php`
```php
<?php

namespace MLflow\Builder;

use MLflow\Model\Trace;
use MLflow\Model\TraceInfo;
use MLflow\Model\TraceData;
use MLflow\Model\MlflowExperimentLocation;
use MLflow\Enum\TraceState;
use MLflow\Enum\SpanType;
use MLflow\Util\TraceIdGenerator;
use MLflow\Util\TimestampHelper;

class TraceBuilder
{
    private string $traceId;
    private string $experimentId;
    private string $name;
    private int $startTimeNs;
    private array $spans = [];
    private array $tags = [];
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
```

### 7.2 SpanBuilder

#### `src/Builder/SpanBuilder.php`
```php
<?php

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
    private array $attributes;
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
```

---

## 8. Phase 6: Integration with MLflowClient

#### `src/MLflowClient.php` (Update)
```php
class MLflowClient
{
    private ?TraceApi $traceApi = null;

    public function traces(): TraceApi
    {
        if ($this->traceApi === null) {
            $this->traceApi = new TraceApi($this->httpClient, $this->logger);
        }
        return $this->traceApi;
    }

    /**
     * Create a trace builder for fluent API
     */
    public function createTraceBuilder(string $experimentId, string $name): TraceBuilder
    {
        return new TraceBuilder($experimentId, $name);
    }
}
```

---

## 9. Usage Examples

### 9.1 Simple Trace with Builder

```php
use MLflow\MLflowClient;
use MLflow\Enum\SpanType;

$client = new MLflowClient('http://localhost:5000');

$trace = $client->createTraceBuilder($experimentId, 'simple-llm-call')
    ->withTag('environment', 'production')
    ->startSpan('llm-generation', SpanType::LLM)
        ->withInput('prompt', 'What is MLflow?')
        ->withAttribute('model', 'gpt-4')
        ->withAttribute('temperature', 0.7)
        ->withOutput('response', 'MLflow is an open-source platform...')
        ->withAttribute('tokens', 150)
        ->end()
    ->build();

// Send trace to MLflow
$traceInfo = $client->traces()->logTrace($trace);

echo "Trace ID: {$traceInfo->getTraceId()}\n";
echo "Duration: {$traceInfo->getExecutionDuration()}ms\n";
```

### 9.2 Nested Spans (RAG Pipeline)

```php
$trace = $client->createTraceBuilder($experimentId, 'rag-pipeline')
    ->withTag('user_id', 'user-123')
    ->withTag('session_id', 'session-456')
    ->startSpan('embedding', SpanType::EMBEDDING)
        ->withInput('query', 'What is MLflow tracing?')
        ->withAttribute('model', 'text-embedding-ada-002')
        ->withOutput('embedding', [0.1, 0.2, ...])
        ->end()
    ->startSpan('retrieval', SpanType::RETRIEVER)
        ->withInput('embedding', [0.1, 0.2, ...])
        ->withAttribute('top_k', 5)
        ->withAttribute('index', 'knowledge-base')
        ->withOutput('documents', $retrievedDocs)
        ->withOutput('scores', [0.95, 0.89, 0.87, 0.82, 0.80])
        ->end()
    ->startSpan('reranking', SpanType::RERANKER)
        ->withInput('documents', $retrievedDocs)
        ->withInput('query', 'What is MLflow tracing?')
        ->withAttribute('model', 'cross-encoder')
        ->withOutput('ranked_documents', $rankedDocs)
        ->end()
    ->startSpan('generation', SpanType::LLM)
        ->withInput('context', $rankedDocs)
        ->withInput('query', 'What is MLflow tracing?')
        ->withAttribute('model', 'gpt-4')
        ->withAttribute('temperature', 0.7)
        ->withAttribute('max_tokens', 500)
        ->withOutput('response', $finalResponse)
        ->withOutput('token_usage', ['prompt' => 1200, 'completion' => 150])
        ->end()
    ->build();

$client->traces()->logTrace($trace);
```

### 9.3 Error Handling

```php
$builder = $client->createTraceBuilder($experimentId, 'error-example');

try {
    $spanBuilder = $builder->startSpan('risky-operation', SpanType::TOOL)
        ->withInput('file', '/path/to/file.txt');

    // Something goes wrong
    throw new \RuntimeException('File not found');

} catch (\Throwable $e) {
    $spanBuilder
        ->withError($e)
        ->end();

    $trace = $builder->build();
    $client->traces()->logTrace($trace);

    // Trace will have ERROR state
}
```

### 9.4 Searching Traces

```php
$result = $client->traces()->searchTraces(
    experimentIds: [$experimentId],
    filterString: "tags.environment = 'production' AND attributes.`model` = 'gpt-4'",
    maxResults: 50,
    orderBy: ['request_time DESC']
);

foreach ($result['traces'] as $trace) {
    echo "Trace: {$trace->getInfo()->getTraceId()}\n";
    echo "Duration: {$trace->getInfo()->getExecutionDuration()}ms\n";
    echo "Spans: " . count($trace->getData()->getSpans()) . "\n";
}

// Pagination
if ($result['next_page_token']) {
    $nextPage = $client->traces()->searchTraces(
        experimentIds: [$experimentId],
        pageToken: $result['next_page_token']
    );
}
```

---

## 10. Implementation Checklist

### Phase 1: Core Models (Week 1)
- [ ] `TraceInfo` with all properties from source
- [ ] `TraceLocation`, `MlflowExperimentLocation`
- [ ] `Trace` and `TraceData`
- [ ] `Span` with nanosecond timestamps
- [ ] `SpanEvent`
- [ ] Unit tests for all models

### Phase 2: Enums & Utils (Week 1)
- [ ] `SpanType` (class with constants)
- [ ] `SpanStatusCode` enum
- [ ] `TraceState` enum
- [ ] `TraceIdGenerator` (32-char trace, 16-char span)
- [ ] `TimestampHelper` (ns and ms support)
- [ ] Unit tests for utilities

### Phase 3: API Client (Week 2)
- [ ] `TraceApi` with V3 endpoints
- [ ] `getTrace()`
- [ ] `searchTraces()` with pagination
- [ ] `logTrace()`
- [ ] `deleteTraces()`
- [ ] `setTraceTag()`, `deleteTraceTag()`
- [ ] Integration tests

### Phase 4: Builder API (Week 2-3)
- [ ] `TraceBuilder`
- [ ] `SpanBuilder`
- [ ] Nested span support
- [ ] Error handling
- [ ] Builder tests

### Phase 5: Integration (Week 3)
- [ ] Update `MLflowClient`
- [ ] Add `traces()` method
- [ ] Add `createTraceBuilder()` helper
- [ ] Update base URI handling for V3
- [ ] Integration tests

### Phase 6: Documentation (Week 4)
- [ ] Update README with tracing examples
- [ ] Create `docs/TRACING_GUIDE.md`
- [ ] Add code examples in `examples/tracing/`
- [ ] API documentation
- [ ] Migration guide if needed

---

## 11. Key Differences from Initial Plan

1. **✅ Timestamps**: Spans use **nanoseconds**, TraceInfo uses **milliseconds**
2. **✅ Field Names**: `trace_id` (not `request_id`), `state` (not `status`)
3. **✅ TraceInfo Structure**: Added `trace_location`, `request_preview`, etc.
4. **✅ ID Format**: Hex strings (32-char trace, 16-char span), not UUIDs
5. **✅ SpanType**: Class with constants (not enum) for extensibility
6. **✅ API Version**: Use V3 (`/api/3.0/mlflow/traces`)
7. **✅ Trace Lifecycle**: Build complete, then log (not start/end separately)
8. **✅ SpanStatusCode**: Renamed from `SpanStatus`

---

## 12. Testing Strategy

### Unit Tests
```php
// tests/Model/SpanTest.php
public function testSpanWithNanosecondTimestamps(): void
{
    $startNs = TimestampHelper::nowNs();
    $endNs = $startNs + 1_000_000_000; // 1 second later

    $span = new Span(
        traceId: '0123456789abcdef0123456789abcdef',
        spanId: '0123456789abcdef',
        name: 'test',
        startTimeNs: $startNs,
        endTimeNs: $endNs,
        parentId: null,
        status: SpanStatusCode::OK,
        spanType: SpanType::TOOL
    );

    $this->assertEquals(1_000_000_000, $span->getDurationNs());
    $this->assertEquals(1000.0, $span->getDurationMs());
}

// tests/Util/TraceIdGeneratorTest.php
public function testGenerateValidIds(): void
{
    $traceId = TraceIdGenerator::generateTraceId();
    $spanId = TraceIdGenerator::generateSpanId();

    $this->assertEquals(32, strlen($traceId));
    $this->assertEquals(16, strlen($spanId));
    $this->assertTrue(TraceIdGenerator::isValidTraceId($traceId));
    $this->assertTrue(TraceIdGenerator::isValidSpanId($spanId));
}
```

---

## 13. Success Criteria

- ✅ Data models match actual MLflow source code structure
- ✅ ID generation produces OpenTelemetry-compatible hex strings
- ✅ Timestamps use correct units (ns for spans, ms for traces)
- ✅ API client uses V3 endpoints
- ✅ Builder API creates valid traces
- ✅ Integration tests pass against real MLflow server (2.14+)
- ✅ PHPStan level 9 passes
- ✅ Test coverage > 90%

---

## 14. References

**Official MLflow Source Code:**
- [`mlflow/tracking/client.py`](https://github.com/mlflow/mlflow/blob/master/mlflow/tracking/client.py)
- [`mlflow/store/tracking/rest_store.py`](https://github.com/mlflow/mlflow/blob/master/mlflow/store/tracking/rest_store.py)
- [`mlflow/entities/trace_info.py`](https://github.com/mlflow/mlflow/blob/master/mlflow/entities/trace_info.py)
- [`mlflow/entities/trace.py`](https://github.com/mlflow/mlflow/blob/master/mlflow/entities/trace.py)
- [`mlflow/entities/span.py`](https://github.com/mlflow/mlflow/blob/master/mlflow/entities/span.py)
- [`mlflow/tracing/utils/__init__.py`](https://github.com/mlflow/mlflow/blob/master/mlflow/tracing/utils/__init__.py)

**Documentation:**
- [MLflow Tracing Quickstart](https://mlflow.org/docs/latest/genai/tracing/quickstart/)
- [MLflow Client API](https://mlflow.org/docs/latest/api_reference/python_api/mlflow.client.html)
- [OpenTelemetry Specification](https://opentelemetry.io/docs/specs/otel/)
