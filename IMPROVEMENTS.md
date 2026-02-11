# MLflow PHP Client - Comprehensive Improvement Plan

**Generated**: 2026-02-11
**Current Status**: PHP 8.4, PHPStan Level 9 ✅, Modern readonly classes ✅

---

## 📊 Executive Summary

The MLflow PHP client demonstrates strong modern PHP practices with excellent use of PHP 8.4 features (readonly classes, enums, promoted properties). Recent modernization efforts have eliminated most primitive obsession. However, opportunities exist for improved clean code, type safety, and developer experience.

**Code Quality Score**: 7.6/10

### Key Strengths
- ✅ Modern PHP 8.4+ features (readonly, enums, promoted properties)
- ✅ Consistent API layer with good error handling
- ✅ Rich collection APIs with generics
- ✅ Good separation of concerns
- ✅ PHPStan level 9 with no errors

### Key Weaknesses
- ❌ Code duplication (formatTags method in 4 files)
- ❌ Inconsistent immutability (Webhook class)
- ❌ Single exception type (no hierarchy)
- ❌ Missing interfaces
- ❌ Some primitive obsession remains (Experiment tags)

---

## 🎯 Improvement Categories

- **Clean Code**: 12 improvements
- **Type Safety**: 9 improvements
- **Developer Experience**: 10 improvements
- **Testing & Quality**: 5 improvements
- **Documentation**: 5 improvements
- **Performance**: 4 improvements
- **Security**: 4 improvements
- **Architecture**: 4 improvements
- **Packaging**: 3 improvements

**Total**: 60+ improvements identified
**Estimated Effort**: 6-8 weeks for high/medium priority items

---

## 🚀 Implementation Roadmap

### Phase 1: Foundation (Week 1-2)
**Goal**: Fix critical code quality issues and establish solid foundation

- [ ] Extract formatTags() duplication
- [ ] Create exception hierarchy
- [ ] Fix Webhook/Experiment consistency
- [ ] Add validation in fromArray()
- [ ] Create constant classes

### Phase 2: Type Safety (Week 3-4)
**Goal**: Strengthen type system and contracts

- [ ] Create MLflowConfig value object
- [ ] Add interfaces (ModelInterface, ApiInterface, CollectionInterface)
- [ ] Fix ModelApi return types
- [ ] Add ValidationHelper utility
- [ ] Create WebhookStatus enum

### Phase 3: Developer Experience (Week 5-6)
**Goal**: Improve API ergonomics and usability

- [ ] Add builder classes (RunBuilder, ExperimentBuilder, ModelBuilder)
- [ ] Complete collection APIs (merge, filter methods)
- [ ] Add connection validation
- [ ] Add usage examples in docblocks
- [ ] Create factory methods for common scenarios

### Phase 4: Polish (Week 7-8)
**Goal**: Testing, documentation, performance, security

- [ ] Add integration test suite
- [ ] Generate API documentation
- [ ] Add performance optimizations
- [ ] Security hardening (input validation, path traversal protection)

---

# Phase 1: Foundation (Week 1-2)

## 1.1 Eliminate `formatTags()` Duplication

**Priority**: 🔴 High
**Effort**: 1 hour
**Impact**: Maintenance, DRY principle

### Current State
Method duplicated in 4 files:
- `src/Api/ExperimentApi.php:246-261`
- `src/Api/RunApi.php:527-537`
- `src/Api/ModelApi.php:145-160`
- `src/Api/ModelRegistryApi.php:609-619`

### Solution
Extract to `BaseApi` as protected method:

```php
// In src/Api/BaseApi.php
protected function formatTags(array $tags): array
{
    $formatted = [];
    foreach ($tags as $key => $value) {
        $formatted[] = [
            'key' => (string) $key,
            'value' => (string) $value,
        ];
    }
    return $formatted;
}
```

### Tasks
- [ ] Add `formatTags()` to BaseApi
- [ ] Replace implementation in ExperimentApi
- [ ] Replace implementation in RunApi
- [ ] Replace implementation in ModelApi
- [ ] Replace implementation in ModelRegistryApi
- [ ] Run tests to verify

---

## 1.2 Create Exception Hierarchy

**Priority**: 🔴 High
**Effort**: 3 hours
**Impact**: Error handling, API usability

### Current State
Single `MLflowException` for all errors. Can't catch specific error types.

### Solution
Create exception hierarchy:

```php
namespace MLflow\Exception;

// Base exception
class MLflowException extends \Exception
{
    public function __construct(
        string $message,
        int $code = 0,
        private readonly ?array $context = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getContext(): ?array
    {
        return $this->context;
    }
}

// Network/Communication Errors
class NetworkException extends MLflowException {}
class TimeoutException extends NetworkException {}

// API Errors
class ApiException extends MLflowException {}
class NotFoundException extends ApiException {}
class ValidationException extends ApiException {}
class AuthenticationException extends ApiException {}
class RateLimitException extends ApiException {}
class ConflictException extends ApiException {}

// Client Errors
class ConfigurationException extends MLflowException {}
class InvalidArgumentException extends MLflowException {}
```

### Update `fromHttpError()` method

```php
public static function fromHttpError(int $statusCode, string $message, ?array $body = null): self
{
    return match($statusCode) {
        404 => new NotFoundException($message, $statusCode, $body),
        401, 403 => new AuthenticationException($message, $statusCode, $body),
        429 => new RateLimitException($message, $statusCode, $body),
        408, 504 => new TimeoutException($message, $statusCode, $body),
        409 => new ConflictException($message, $statusCode, $body),
        400, 422 => new ValidationException($message, $statusCode, $body),
        default => new ApiException($message, $statusCode, $body),
    };
}
```

### Tasks
- [ ] Create exception classes in `src/Exception/`
- [ ] Update `MLflowException::fromHttpError()`
- [ ] Update BaseApi to use specific exceptions
- [ ] Update README with exception handling examples
- [ ] Add tests for exception hierarchy
- [ ] Update PHPDoc @throws annotations

---

## 1.3 Fix Webhook Immutability

**Priority**: 🔴 High
**Effort**: 2 hours
**Impact**: Consistency, API design

### Current State
`src/Model/Webhook.php` uses private properties with getters, not readonly class.

### Solution
Convert to readonly class with public properties:

```php
readonly class Webhook implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $name,
        public array $events,
        public string $url,
        public WebhookStatus $status, // Use enum
        public ?string $description = null,
        public ?array $httpHeaders = null,
        public ?string $creationTimestamp = null,
        public ?string $lastModifiedTimestamp = null,
    ) {}

    public function isActive(): bool
    {
        return $this->status === WebhookStatus::ACTIVE;
    }

    // Deprecated methods for backward compatibility
    /** @deprecated Use public property $id instead */
    public function getId(): string
    {
        return $this->id;
    }

    // ... other deprecated getters

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? '',
            name: $data['name'] ?? '',
            events: $data['events'] ?? [],
            url: $data['url'] ?? '',
            status: WebhookStatus::from($data['status'] ?? 'ACTIVE'),
            description: $data['description'] ?? null,
            httpHeaders: $data['http_headers'] ?? null,
            creationTimestamp: $data['creation_timestamp'] ?? null,
            lastModifiedTimestamp: $data['last_modified_timestamp'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'events' => $this->events,
            'url' => $this->url,
            'status' => $this->status->value,
            'description' => $this->description,
            'http_headers' => $this->httpHeaders,
            'creation_timestamp' => $this->creationTimestamp,
            'last_modified_timestamp' => $this->lastModifiedTimestamp,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
```

### Tasks
- [ ] Create `WebhookStatus` enum
- [ ] Convert Webhook to readonly class
- [ ] Add deprecated getters for BC
- [ ] Update tests
- [ ] Update documentation

---

## 1.4 Fix Experiment Tags (Primitive Obsession)

**Priority**: 🔴 High
**Effort**: 1 hour
**Impact**: Consistency, type safety

### Current State
`src/Model/Experiment.php:19` uses `array<string, mixed>|null` for tags instead of `TagCollection`.

### Solution
```php
readonly class Experiment implements \JsonSerializable
{
    public function __construct(
        public string $experimentId,
        public string $name,
        public string $artifactLocation,
        public LifecycleStage $lifecycleStage,
        public ?string $lastUpdateTime = null,
        public ?string $creationTime = null,
        public ?TagCollection $tags = null, // Changed from array
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            experimentId: $data['experiment_id'] ?? '',
            name: $data['name'] ?? '',
            artifactLocation: $data['artifact_location'] ?? '',
            lifecycleStage: LifecycleStage::from($data['lifecycle_stage'] ?? 'active'),
            lastUpdateTime: $data['last_update_time'] ?? null,
            creationTime: $data['creation_time'] ?? null,
            tags: isset($data['tags'])
                ? TagCollection::fromArray($data['tags'])
                : null,
        );
    }

    public function toArray(): array
    {
        return [
            'experiment_id' => $this->experimentId,
            'name' => $this->name,
            'artifact_location' => $this->artifactLocation,
            'lifecycle_stage' => $this->lifecycleStage->value,
            'last_update_time' => $this->lastUpdateTime,
            'creation_time' => $this->creationTime,
            'tags' => $this->tags?->toArray(),
        ];
    }
}
```

### Tasks
- [ ] Update Experiment model to use TagCollection
- [ ] Update ExperimentApi to handle TagCollection
- [ ] Update tests
- [ ] Verify backward compatibility

---

## 1.5 Add Validation in `fromArray()` Methods

**Priority**: 🔴 High
**Effort**: 4 hours
**Impact**: Data integrity, error messages

### Current State
Models accept invalid data without exceptions. Example from `RunInfo`:

```php
$runId = $data['run_id'] ?? $data['run_uuid'] ?? ''; // Defaults to empty string
$experimentId = $data['experiment_id'] ?? '';        // Defaults to empty string
```

### Solution
Create `ValidationHelper` utility:

```php
namespace MLflow\Util;

use MLflow\Exception\ValidationException;

final class ValidationHelper
{
    public static function requireString(array $data, string $key, ?string $fallbackKey = null): string
    {
        $value = $data[$key] ?? ($fallbackKey ? $data[$fallbackKey] ?? null : null);

        if (!is_string($value) || $value === '') {
            $message = $fallbackKey
                ? "Required field missing: '{$key}' or '{$fallbackKey}'"
                : "Required field missing: '{$key}'";
            throw new ValidationException($message);
        }

        return $value;
    }

    public static function optionalString(array $data, string $key, ?string $default = null): ?string
    {
        if (!isset($data[$key])) {
            return $default;
        }

        return is_string($data[$key]) ? $data[$key] : $default;
    }

    public static function requireInt(array $data, string $key): int
    {
        if (!isset($data[$key])) {
            throw new ValidationException("Required field missing: '{$key}'");
        }

        if (!is_int($data[$key])) {
            throw new ValidationException("Field '{$key}' must be an integer");
        }

        return $data[$key];
    }

    public static function optionalInt(array $data, string $key, ?int $default = null): ?int
    {
        if (!isset($data[$key])) {
            return $default;
        }

        return is_int($data[$key]) ? $data[$key] : $default;
    }

    public static function requireFloat(array $data, string $key): float
    {
        if (!isset($data[$key])) {
            throw new ValidationException("Required field missing: '{$key}'");
        }

        if (!is_numeric($data[$key])) {
            throw new ValidationException("Field '{$key}' must be numeric");
        }

        return (float) $data[$key];
    }

    public static function requireArray(array $data, string $key): array
    {
        if (!isset($data[$key])) {
            throw new ValidationException("Required field missing: '{$key}'");
        }

        if (!is_array($data[$key])) {
            throw new ValidationException("Field '{$key}' must be an array");
        }

        return $data[$key];
    }

    public static function optionalArray(array $data, string $key, ?array $default = null): ?array
    {
        if (!isset($data[$key])) {
            return $default;
        }

        return is_array($data[$key]) ? $data[$key] : $default;
    }
}
```

### Update RunInfo example:

```php
use MLflow\Util\ValidationHelper;

public static function fromArray(array $data): self
{
    return new self(
        runId: ValidationHelper::requireString($data, 'run_id', 'run_uuid'),
        experimentId: ValidationHelper::requireString($data, 'experiment_id'),
        status: RunStatus::from($data['status'] ?? 'RUNNING'),
        startTime: ValidationHelper::optionalInt($data, 'start_time'),
        endTime: ValidationHelper::optionalInt($data, 'end_time'),
        // ... other fields
    );
}
```

### Tasks
- [ ] Create ValidationHelper utility class
- [ ] Update RunInfo::fromArray() with validation
- [ ] Update Experiment::fromArray() with validation
- [ ] Update Run::fromArray() with validation
- [ ] Update ModelVersion::fromArray() with validation
- [ ] Update all other model fromArray() methods
- [ ] Add unit tests for ValidationHelper
- [ ] Add integration tests for validation errors

---

## 1.6 Create Constant Classes

**Priority**: 🟡 Medium
**Effort**: 2 hours
**Impact**: Maintainability, magic strings elimination

### Current State
Magic values scattered throughout:
- HTTP status codes: 404, 401, 403, 429, etc.
- MLflow field names: 'run_id', 'experiment_id', etc.
- API endpoints: 'mlflow/experiments/create', etc.

### Solution

```php
namespace MLflow\Constants;

final class HttpStatus
{
    public const OK = 200;
    public const CREATED = 201;
    public const NO_CONTENT = 204;
    public const BAD_REQUEST = 400;
    public const UNAUTHORIZED = 401;
    public const FORBIDDEN = 403;
    public const NOT_FOUND = 404;
    public const CONFLICT = 409;
    public const TIMEOUT = 408;
    public const RATE_LIMITED = 429;
    public const INTERNAL_ERROR = 500;
    public const GATEWAY_TIMEOUT = 504;

    private function __construct() {}
}
```

```php
namespace MLflow\Constants;

final class MLflowEndpoints
{
    // Experiments
    public const EXPERIMENTS_CREATE = 'mlflow/experiments/create';
    public const EXPERIMENTS_GET = 'mlflow/experiments/get';
    public const EXPERIMENTS_GET_BY_NAME = 'mlflow/experiments/get-by-name';
    public const EXPERIMENTS_DELETE = 'mlflow/experiments/delete';
    public const EXPERIMENTS_RESTORE = 'mlflow/experiments/restore';
    public const EXPERIMENTS_UPDATE = 'mlflow/experiments/update';
    public const EXPERIMENTS_SEARCH = 'mlflow/experiments/search';
    public const EXPERIMENTS_SET_TAG = 'mlflow/experiments/set-experiment-tag';

    // Runs
    public const RUNS_CREATE = 'mlflow/runs/create';
    public const RUNS_GET = 'mlflow/runs/get';
    public const RUNS_UPDATE = 'mlflow/runs/update';
    public const RUNS_DELETE = 'mlflow/runs/delete';
    public const RUNS_RESTORE = 'mlflow/runs/restore';
    public const RUNS_SEARCH = 'mlflow/runs/search';
    public const RUNS_LOG_METRIC = 'mlflow/runs/log-metric';
    public const RUNS_LOG_BATCH = 'mlflow/runs/log-batch';
    public const RUNS_LOG_PARAMETER = 'mlflow/runs/log-parameter';

    // Model Registry
    public const MODEL_REGISTRY_CREATE = 'mlflow/registered-models/create';
    public const MODEL_REGISTRY_GET = 'mlflow/registered-models/get';
    public const MODEL_REGISTRY_UPDATE = 'mlflow/registered-models/update';
    public const MODEL_REGISTRY_DELETE = 'mlflow/registered-models/delete';
    public const MODEL_REGISTRY_SEARCH = 'mlflow/registered-models/search';

    private function __construct() {}
}
```

```php
namespace MLflow\Constants;

final class MLflowFields
{
    // Common
    public const RUN_ID = 'run_id';
    public const RUN_UUID = 'run_uuid';
    public const EXPERIMENT_ID = 'experiment_id';
    public const USER_ID = 'user_id';

    // Run fields
    public const START_TIME = 'start_time';
    public const END_TIME = 'end_time';
    public const STATUS = 'status';
    public const LIFECYCLE_STAGE = 'lifecycle_stage';

    // Metric fields
    public const KEY = 'key';
    public const VALUE = 'value';
    public const TIMESTAMP = 'timestamp';
    public const STEP = 'step';

    private function __construct() {}
}
```

### Tasks
- [ ] Create HttpStatus constants class
- [ ] Create MLflowEndpoints constants class
- [ ] Create MLflowFields constants class
- [ ] Replace magic numbers in exception handling
- [ ] Replace magic strings in API classes
- [ ] Replace magic strings in model classes
- [ ] Update tests
- [ ] Update documentation

---

# Phase 2: Type Safety (Week 3-4)

## 2.1 Create `MLflowConfig` Value Object

**Priority**: 🔴 High
**Effort**: 3 hours
**Impact**: Type safety, validation, DX

### Current State
`MLflowClient` constructor accepts primitive `array $config` with no validation or IDE support.

### Solution

```php
namespace MLflow\Config;

readonly class MLflowConfig
{
    public function __construct(
        public float $timeout = 30.0,
        public int $connectTimeout = 10,
        public int $maxRetries = 3,
        public float $retryDelay = 1.0,
        public array $headers = [],
        public bool $verify = true,
        public ?string $proxy = null,
        public ?string $cert = null,
        public ?string $sslKey = null,
        public bool $debug = false,
    ) {
        if ($this->timeout <= 0) {
            throw new ConfigurationException('Timeout must be positive');
        }

        if ($this->maxRetries < 0) {
            throw new ConfigurationException('Max retries cannot be negative');
        }
    }

    public static function fromArray(array $config): self
    {
        return new self(
            timeout: $config['timeout'] ?? 30.0,
            connectTimeout: $config['connect_timeout'] ?? 10,
            maxRetries: $config['retries'] ?? 3,
            retryDelay: $config['retry_delay'] ?? 1.0,
            headers: $config['headers'] ?? [],
            verify: $config['verify'] ?? true,
            proxy: $config['proxy'] ?? null,
            cert: $config['cert'] ?? null,
            sslKey: $config['ssl_key'] ?? null,
            debug: $config['debug'] ?? false,
        );
    }

    public function toGuzzleArray(): array
    {
        $config = [
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
            'headers' => $this->headers,
            'verify' => $this->verify,
        ];

        if ($this->proxy !== null) {
            $config['proxy'] = $this->proxy;
        }

        if ($this->cert !== null) {
            $config['cert'] = $this->cert;
        }

        if ($this->sslKey !== null) {
            $config['ssl_key'] = $this->sslKey;
        }

        if ($this->debug) {
            $config['debug'] = true;
        }

        return $config;
    }

    public function withTimeout(float $timeout): self
    {
        return new self(
            timeout: $timeout,
            connectTimeout: $this->connectTimeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            headers: $this->headers,
            verify: $this->verify,
            proxy: $this->proxy,
            cert: $this->cert,
            sslKey: $this->sslKey,
            debug: $this->debug,
        );
    }

    public function withHeaders(array $headers): self
    {
        return new self(
            timeout: $this->timeout,
            connectTimeout: $this->connectTimeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            headers: array_merge($this->headers, $headers),
            verify: $this->verify,
            proxy: $this->proxy,
            cert: $this->cert,
            sslKey: $this->sslKey,
            debug: $this->debug,
        );
    }
}
```

### Update MLflowClient:

```php
public function __construct(
    string $trackingUri,
    MLflowConfig|array $config = [],
    ?LoggerInterface $logger = null
) {
    $this->trackingUri = rtrim($trackingUri, '/');
    $this->config = $config instanceof MLflowConfig
        ? $config
        : MLflowConfig::fromArray($config);
    $this->logger = $logger ?? new NullLogger();
}
```

### Tasks
- [ ] Create MLflowConfig class
- [ ] Add validation in constructor
- [ ] Add factory methods (fromArray, default)
- [ ] Add immutable modifier methods (with*)
- [ ] Update MLflowClient to accept MLflowConfig
- [ ] Maintain backward compatibility with array
- [ ] Update documentation
- [ ] Add tests

---

## 2.2 Add Interfaces for All Layers

**Priority**: 🔴 High
**Effort**: 4 hours
**Impact**: Testability, contracts, extensibility

### Solution

```php
namespace MLflow\Contract;

interface ModelInterface
{
    public static function fromArray(array $data): self;
    public function toArray(): array;
}
```

```php
namespace MLflow\Contract;

interface ApiInterface
{
    public function __construct(
        \GuzzleHttp\ClientInterface $client,
        \Psr\Log\LoggerInterface $logger
    );
}
```

```php
namespace MLflow\Contract;

/**
 * @template T
 */
interface CollectionInterface extends \Countable, \IteratorAggregate
{
    public function all(): array;
    public function isEmpty(): bool;
    public function count(): int;
    public function first(): mixed;
    public function last(): mixed;
    public function filter(callable $callback): self;
    public function map(callable $callback): self;
    public function toArray(): array;
}
```

```php
namespace MLflow\Contract;

interface SerializableModelInterface extends ModelInterface, \JsonSerializable
{
    public function jsonSerialize(): array;
}
```

### Update models:

```php
readonly class Experiment implements SerializableModelInterface
{
    // ... implementation
}
```

### Tasks
- [ ] Create ModelInterface
- [ ] Create SerializableModelInterface
- [ ] Create ApiInterface
- [ ] Create CollectionInterface
- [ ] Update all models to implement interfaces
- [ ] Update all API classes to implement ApiInterface
- [ ] Update all collections to implement CollectionInterface
- [ ] Add interface tests
- [ ] Update documentation

---

## 2.3 Fix ModelApi Return Types

**Priority**: 🔴 High
**Effort**: 2 hours
**Impact**: Type safety, API consistency

### Current State
`ModelApi` returns `array<string, mixed>` instead of typed objects, unlike `ModelRegistryApi`.

### Solution Options

**Option A**: Deprecate ModelApi
```php
/**
 * @deprecated Use ModelRegistryApi instead
 */
final class ModelApi extends BaseApi
{
    // Keep for backward compatibility but mark deprecated
}
```

**Option B**: Fix return types
```php
public function createRegisteredModel(
    string $name,
    array $tags = [],
    ?string $description = null
): RegisteredModel {
    $data = [
        'name' => $name,
        'tags' => $this->formatTags($tags),
        'description' => $description,
    ];

    $response = $this->post('registered-models/create', $data);

    return RegisteredModel::fromArray($response['registered_model'] ?? []);
}

public function getRegisteredModel(string $name): RegisteredModel
{
    $response = $this->get('registered-models/get', ['name' => $name]);

    return RegisteredModel::fromArray($response['registered_model'] ?? []);
}
```

### Recommendation
Option A - Deprecate ModelApi since ModelRegistryApi provides better API.

### Tasks
- [ ] Mark ModelApi as @deprecated
- [ ] Add migration notes to documentation
- [ ] Add trigger_error() deprecation warnings
- [ ] Update examples to use ModelRegistryApi
- [ ] Plan removal for next major version

---

## 2.4 Create WebhookStatus Enum

**Priority**: 🟡 Medium
**Effort**: 30 minutes
**Impact**: Type safety

### Solution

```php
namespace MLflow\Enum;

enum WebhookStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case DISABLED = 'DISABLED';

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isDisabled(): bool
    {
        return $this === self::DISABLED;
    }

    public function getDisplayName(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::DISABLED => 'Disabled',
        };
    }
}
```

### Tasks
- [ ] Create WebhookStatus enum
- [ ] Update Webhook model to use enum
- [ ] Update WebhookApi to accept enum
- [ ] Add tests
- [ ] Update documentation

---

## 2.5 Add ValidationHelper Utility

**Priority**: 🔴 High
**Effort**: Covered in Phase 1.5

See Phase 1.5 for complete implementation.

---

## 2.6 Add Response Validation in API Layer

**Priority**: 🟡 Medium
**Effort**: 3 hours
**Impact**: Robustness, error messages

### Current State
Silent fallbacks with `??` operator without proper validation.

Example from `TraceApi.php:140`:
```php
$deleted = $response['traces_deleted'] ?? 0;
return is_int($deleted) ? $deleted : 0;
```

### Solution

```php
namespace MLflow\Util;

final class ResponseValidator
{
    public static function requireField(array $response, string $field): mixed
    {
        if (!array_key_exists($field, $response)) {
            throw new ValidationException(
                "Expected field '{$field}' missing in API response"
            );
        }

        return $response[$field];
    }

    public static function requireInt(array $response, string $field): int
    {
        $value = self::requireField($response, $field);

        if (!is_int($value)) {
            throw new ValidationException(
                "Field '{$field}' must be an integer, got " . get_debug_type($value)
            );
        }

        return $value;
    }

    public static function requireString(array $response, string $field): string
    {
        $value = self::requireField($response, $field);

        if (!is_string($value)) {
            throw new ValidationException(
                "Field '{$field}' must be a string, got " . get_debug_type($value)
            );
        }

        return $value;
    }

    public static function requireArray(array $response, string $field): array
    {
        $value = self::requireField($response, $field);

        if (!is_array($value)) {
            throw new ValidationException(
                "Field '{$field}' must be an array, got " . get_debug_type($value)
            );
        }

        return $value;
    }
}
```

### Update TraceApi example:

```php
use MLflow\Util\ResponseValidator;

public function deleteTraces(
    string $experimentId,
    ?int $maxTimestampMillis = null,
    ?array $requestIds = null
): int {
    $response = $this->delete('traces/delete-traces', $data);

    return ResponseValidator::requireInt($response, 'traces_deleted');
}
```

### Tasks
- [ ] Create ResponseValidator utility
- [ ] Update API classes to use ResponseValidator
- [ ] Add tests for validation errors
- [ ] Update documentation

---

## 2.7 Add ArrayAccess to MetricCollection

**Priority**: 🟡 Medium
**Effort**: 1 hour
**Impact**: API consistency

### Current State
`ParameterCollection` and `TagCollection` implement `ArrayAccess`, but `MetricCollection` doesn't.

### Solution

```php
/**
 * @implements \ArrayAccess<int, Metric>
 */
final class MetricCollection implements
    \ArrayAccess,
    \Countable,
    \IteratorAggregate,
    \JsonSerializable
{
    // ... existing code

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->metrics[$offset]);
    }

    public function offsetGet(mixed $offset): ?Metric
    {
        return $this->metrics[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!$value instanceof Metric) {
            throw new \InvalidArgumentException(
                'Value must be an instance of Metric'
            );
        }

        if ($offset === null) {
            $this->metrics[] = $value;
        } else {
            $this->metrics[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->metrics[$offset]);
    }
}
```

### Tasks
- [ ] Add ArrayAccess implementation to MetricCollection
- [ ] Add type checking in offsetSet
- [ ] Add tests for ArrayAccess operations
- [ ] Update documentation

---

# Phase 3: Developer Experience (Week 5-6)

## 3.1 Add Builder Classes

**Priority**: 🔴 High
**Effort**: 6 hours
**Impact**: DX, API usability

### RunBuilder

```php
namespace MLflow\Builder;

use MLflow\Api\RunApi;
use MLflow\Model\Run;

final class RunBuilder
{
    private ?string $runName = null;
    private ?int $startTime = null;
    private array $tags = [];
    private array $params = [];
    private array $metrics = [];

    public function __construct(
        private readonly RunApi $runApi,
        private readonly string $experimentId,
    ) {}

    public function withName(string $name): self
    {
        $this->runName = $name;
        return $this;
    }

    public function withTag(string $key, string $value): self
    {
        $this->tags[$key] = $value;
        return $this;
    }

    public function withTags(array $tags): self
    {
        $this->tags = array_merge($this->tags, $tags);
        return $this;
    }

    public function withParam(string $key, string $value): self
    {
        $this->params[$key] = $value;
        return $this;
    }

    public function withParams(array $params): self
    {
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    public function withMetric(string $key, float $value, ?int $step = null): self
    {
        $this->metrics[] = [
            'key' => $key,
            'value' => $value,
            'step' => $step,
            'timestamp' => (int) (microtime(true) * 1000),
        ];
        return $this;
    }

    public function withMetrics(array $metrics): self
    {
        $this->metrics = array_merge($this->metrics, $metrics);
        return $this;
    }

    public function withStartTime(int $timestamp): self
    {
        $this->startTime = $timestamp;
        return $this;
    }

    public function start(): Run
    {
        $run = $this->runApi->create(
            experimentId: $this->experimentId,
            runName: $this->runName,
            startTime: $this->startTime ?? (int) (microtime(true) * 1000),
            tags: $this->tags,
        );

        // Log batch data if any
        if (!empty($this->params) || !empty($this->metrics)) {
            $this->runApi->logBatch(
                runId: $run->getRunId(),
                metrics: $this->metrics,
                params: $this->params,
            );
        }

        return $run;
    }
}
```

### ExperimentBuilder

```php
namespace MLflow\Builder;

use MLflow\Api\ExperimentApi;
use MLflow\Model\Experiment;

final class ExperimentBuilder
{
    private ?string $artifactLocation = null;
    private array $tags = [];

    public function __construct(
        private readonly ExperimentApi $experimentApi,
        private readonly string $name,
    ) {}

    public function withArtifactLocation(string $location): self
    {
        $this->artifactLocation = $location;
        return $this;
    }

    public function withTag(string $key, string $value): self
    {
        $this->tags[$key] = $value;
        return $this;
    }

    public function withTags(array $tags): self
    {
        $this->tags = array_merge($this->tags, $tags);
        return $this;
    }

    public function create(): Experiment
    {
        return $this->experimentApi->create(
            name: $this->name,
            artifactLocation: $this->artifactLocation,
            tags: $this->tags,
        );
    }
}
```

### ModelBuilder

```php
namespace MLflow\Builder;

use MLflow\Api\ModelRegistryApi;
use MLflow\Model\RegisteredModel;

final class ModelBuilder
{
    private ?string $description = null;
    private array $tags = [];

    public function __construct(
        private readonly ModelRegistryApi $registryApi,
        private readonly string $name,
    ) {}

    public function withDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function withTag(string $key, string $value): self
    {
        $this->tags[$key] = $value;
        return $this;
    }

    public function withTags(array $tags): self
    {
        $this->tags = array_merge($this->tags, $tags);
        return $this;
    }

    public function create(): RegisteredModel
    {
        return $this->registryApi->createRegisteredModel(
            name: $this->name,
            description: $this->description,
            tags: $this->tags,
        );
    }
}
```

### Update MLflowClient:

```php
public function createRunBuilder(string $experimentId): RunBuilder
{
    return new RunBuilder($this->runs(), $experimentId);
}

public function createExperimentBuilder(string $name): ExperimentBuilder
{
    return new ExperimentBuilder($this->experiments(), $name);
}

public function createModelBuilder(string $name): ModelBuilder
{
    return new ModelBuilder($this->modelRegistry(), $name);
}
```

### Tasks
- [ ] Create RunBuilder
- [ ] Create ExperimentBuilder
- [ ] Create ModelBuilder
- [ ] Add factory methods to MLflowClient
- [ ] Add comprehensive tests
- [ ] Update README with builder examples
- [ ] Add PHPDoc examples

---

## 3.2 Complete Collection APIs

**Priority**: 🟡 Medium
**Effort**: 2 hours
**Impact**: API consistency

### Add Missing Methods to MetricCollection

```php
final class MetricCollection implements /* ... */
{
    // Add merge like ParameterCollection/TagCollection have
    public function merge(self $other): self
    {
        return new self(array_merge($this->metrics, $other->metrics));
    }

    // Add filter by key prefix
    public function filterByKeyPrefix(string $prefix): self
    {
        return $this->filter(
            fn(Metric $m) => str_starts_with($m->key, $prefix)
        );
    }

    // Add filter by step range
    public function filterByStepRange(int $minStep, int $maxStep): self
    {
        return $this->filter(
            fn(Metric $m) => $m->step >= $minStep && $m->step <= $maxStep
        );
    }

    // Add filter by value range
    public function filterByValueRange(float $min, float $max): self
    {
        return $this->filter(
            fn(Metric $m) => $m->value >= $min && $m->value <= $max
        );
    }

    // Add unique by key (keep first)
    public function uniqueByKey(): self
    {
        $seen = [];
        $unique = [];

        foreach ($this->metrics as $metric) {
            if (!isset($seen[$metric->key])) {
                $seen[$metric->key] = true;
                $unique[] = $metric;
            }
        }

        return new self($unique);
    }

    // Add reduce
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->metrics, $callback, $initial);
    }
}
```

### Tasks
- [ ] Add merge() to MetricCollection
- [ ] Add filterByKeyPrefix() to MetricCollection
- [ ] Add filterByStepRange(), filterByValueRange()
- [ ] Add uniqueByKey() method
- [ ] Add reduce() method to all collections
- [ ] Add tests
- [ ] Update documentation

---

## 3.3 Add Connection Validation

**Priority**: 🟡 Medium
**Effort**: 2 hours
**Impact**: Early error detection, DX

### Solution

```php
namespace MLflow;

final class MLflowClient
{
    // ... existing code

    /**
     * Validate connection to MLflow server
     *
     * @throws NetworkException If server is unreachable
     * @throws ApiException If server returns error
     */
    public function validateConnection(): bool
    {
        try {
            // Try to list experiments (lightweight operation)
            $this->experiments()->search(maxResults: 1);

            return true;
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw new NetworkException(
                "Cannot connect to MLflow server at {$this->trackingUri}",
                0,
                ['uri' => $this->trackingUri],
                $e
            );
        }
    }

    /**
     * Get server information
     */
    public function getServerInfo(): array
    {
        try {
            $response = $this->httpClient->get('version');
            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'version' => $data['version'] ?? 'unknown',
                'reachable' => true,
            ];
        } catch (\Exception $e) {
            return [
                'version' => null,
                'reachable' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
```

### Tasks
- [ ] Add validateConnection() method
- [ ] Add getServerInfo() method
- [ ] Add connection timeout handling
- [ ] Add tests
- [ ] Update documentation

---

## 3.4 Add Usage Examples in DocBlocks

**Priority**: 🟡 Medium
**Effort**: 4 hours
**Impact**: Documentation, learning curve

### Example - ExperimentApi

```php
/**
 * MLflow Experiments API
 *
 * Provides methods for creating, reading, updating, and deleting experiments.
 *
 * @example Basic usage
 * ```php
 * $client = new MLflowClient('http://localhost:5000');
 * $experiments = $client->experiments();
 *
 * // Create experiment
 * $exp = $experiments->create('my-experiment', tags: ['team' => 'ml']);
 *
 * // Search experiments
 * $results = $experiments->search(
 *     filter: "attribute.name LIKE 'my-%'",
 *     orderBy: ['creation_time DESC']
 * );
 *
 * // Update experiment
 * $experiments->update($exp->experimentId, 'new-name');
 * ```
 *
 * @example With builder pattern
 * ```php
 * $exp = $client->createExperimentBuilder('my-experiment')
 *     ->withArtifactLocation('s3://bucket/path')
 *     ->withTag('version', 'v1.0')
 *     ->create();
 * ```
 */
final class ExperimentApi extends BaseApi
{
    /**
     * Create a new experiment
     *
     * @param string $name Experiment name (must be unique)
     * @param string|null $artifactLocation Location for storing artifacts
     * @param array<string, string> $tags Optional tags
     *
     * @return Experiment The created experiment
     *
     * @throws ValidationException If name is empty
     * @throws ConflictException If experiment with name already exists
     * @throws ApiException On other API errors
     *
     * @example
     * ```php
     * $exp = $api->create(
     *     name: 'my-experiment',
     *     artifactLocation: 's3://bucket/path',
     *     tags: ['team' => 'ml', 'project' => 'recommendation']
     * );
     * ```
     */
    public function create(
        string $name,
        ?string $artifactLocation = null,
        array $tags = []
    ): Experiment {
        // ... implementation
    }
}
```

### Tasks
- [ ] Add class-level examples to all API classes
- [ ] Add method-level examples for complex methods
- [ ] Add builder pattern examples
- [ ] Add error handling examples
- [ ] Review and improve existing docblocks
- [ ] Generate documentation site

---

## 3.5 Create Factory Methods

**Priority**: 🟡 Medium
**Effort**: 2 hours
**Impact**: DX, convenience

### Examples

```php
// In Run model
readonly class Run implements SerializableModelInterface
{
    // ... existing code

    public static function createForExperiment(
        string $experimentId,
        string $runName,
        array $tags = []
    ): self {
        $runInfo = new RunInfo(
            runId: '', // Will be set by server
            experimentId: $experimentId,
            status: RunStatus::RUNNING,
            startTime: (int) (microtime(true) * 1000),
            runName: $runName,
        );

        $runData = new RunData(
            tags: TagCollection::fromAssociativeArray($tags),
        );

        return new self($runInfo, $runData);
    }
}
```

```php
// In Metric model
readonly class Metric implements SerializableModelInterface
{
    // ... existing code

    public static function now(string $key, float $value, ?int $step = null): self
    {
        return new self(
            key: $key,
            value: $value,
            timestamp: (int) (microtime(true) * 1000),
            step: $step,
        );
    }

    public static function atTimestamp(
        string $key,
        float $value,
        int $timestamp,
        ?int $step = null
    ): self {
        return new self(
            key: $key,
            value: $value,
            timestamp: $timestamp,
            step: $step,
        );
    }
}
```

### Tasks
- [ ] Add factory methods to key models (Run, Metric, Param)
- [ ] Add convenience constructors
- [ ] Add tests
- [ ] Update documentation with examples

---

# Phase 4: Polish (Week 7-8)

## 4.1 Add Integration Test Suite

**Priority**: 🔴 High
**Effort**: 8 hours
**Impact**: Quality, confidence

### Setup

```php
// tests/Integration/IntegrationTestCase.php
namespace MLflow\Tests\Integration;

use MLflow\MLflowClient;
use PHPUnit\Framework\TestCase;

abstract class IntegrationTestCase extends TestCase
{
    protected MLflowClient $client;
    protected string $testExperimentId;

    protected function setUp(): void
    {
        parent::setUp();

        // Require MLFLOW_TRACKING_URI environment variable
        $trackingUri = getenv('MLFLOW_TRACKING_URI');

        if ($trackingUri === false) {
            $this->markTestSkipped(
                'Integration tests require MLFLOW_TRACKING_URI environment variable'
            );
        }

        $this->client = new MLflowClient($trackingUri);

        // Create test experiment
        $exp = $this->client->experiments()->create(
            'test-' . uniqid(),
            tags: ['test' => 'true']
        );

        $this->testExperimentId = $exp->experimentId;
    }

    protected function tearDown(): void
    {
        // Clean up test experiment
        if (isset($this->testExperimentId)) {
            try {
                $this->client->experiments()->deleteExperiment(
                    $this->testExperimentId
                );
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }

        parent::tearDown();
    }
}
```

### Example Integration Test

```php
// tests/Integration/RunLifecycleTest.php
namespace MLflow\Tests\Integration;

final class RunLifecycleTest extends IntegrationTestCase
{
    public function test_complete_run_lifecycle(): void
    {
        // Create run
        $run = $this->client->runs()->create(
            experimentId: $this->testExperimentId,
            runName: 'integration-test-run',
            tags: ['test' => 'integration']
        );

        $this->assertNotEmpty($run->getRunId());
        $this->assertEquals($this->testExperimentId, $run->getExperimentId());

        // Log parameters
        $this->client->runs()->logParameter($run->getRunId(), 'lr', '0.01');
        $this->client->runs()->logParameter($run->getRunId(), 'batch_size', '32');

        // Log metrics
        $this->client->runs()->logMetric($run->getRunId(), 'accuracy', 0.95);
        $this->client->runs()->logMetric($run->getRunId(), 'loss', 0.05);

        // Retrieve run and verify data
        $retrieved = $this->client->runs()->getRun($run->getRunId());

        $this->assertCount(2, $retrieved->getData()->params ?? []);
        $this->assertCount(2, $retrieved->getData()->metrics ?? []);

        // Update run status
        $this->client->runs()->setTerminated($run->getRunId(), 'FINISHED');

        // Verify final state
        $final = $this->client->runs()->getRun($run->getRunId());
        $this->assertEquals('FINISHED', $final->getStatus()->value);
        $this->assertNotNull($final->getEndTime());
    }

    public function test_batch_logging(): void
    {
        $run = $this->client->runs()->create(
            experimentId: $this->testExperimentId,
            runName: 'batch-test'
        );

        // Log batch data
        $this->client->runs()->logBatch(
            runId: $run->getRunId(),
            metrics: [
                ['key' => 'accuracy', 'value' => 0.95, 'step' => 1],
                ['key' => 'accuracy', 'value' => 0.96, 'step' => 2],
                ['key' => 'loss', 'value' => 0.05, 'step' => 1],
            ],
            params: [
                'lr' => '0.01',
                'optimizer' => 'adam',
            ],
            tags: [
                'model' => 'resnet',
            ]
        );

        // Verify
        $retrieved = $this->client->runs()->getRun($run->getRunId());
        $this->assertCount(2, $retrieved->getData()->params ?? []);
    }
}
```

### Docker Setup

```yaml
# docker-compose.test.yml
version: '3.8'

services:
  mlflow:
    image: ghcr.io/mlflow/mlflow:v2.10.0
    ports:
      - "5000:5000"
    command: mlflow server --host 0.0.0.0 --backend-store-uri sqlite:///mlflow.db --default-artifact-root ./artifacts
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:5000/health"]
      interval: 5s
      timeout: 3s
      retries: 10

  php:
    image: php:8.4-cli
    volumes:
      - .:/app
    working_dir: /app
    command: vendor/bin/phpunit tests/Integration
    environment:
      MLFLOW_TRACKING_URI: http://mlflow:5000
    depends_on:
      mlflow:
        condition: service_healthy
```

### Tasks
- [ ] Create IntegrationTestCase base class
- [ ] Add integration tests for experiments
- [ ] Add integration tests for runs
- [ ] Add integration tests for model registry
- [ ] Add integration tests for artifacts
- [ ] Add Docker Compose setup
- [ ] Add CI workflow for integration tests
- [ ] Document how to run integration tests

---

## 4.2 Generate API Documentation

**Priority**: 🟡 Medium
**Effort**: 3 hours
**Impact**: Documentation, onboarding

### Setup phpDocumentor

```xml
<!-- phpdoc.xml -->
<?xml version="1.0" encoding="UTF-8" ?>
<phpdocumentor
    configVersion="3"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns="https://www.phpdoc.org"
    xsi:noNamespaceSchemaLocation="https://docs.phpdoc.org/latest/phpdoc.xsd"
>
    <title>MLflow PHP Client</title>
    <paths>
        <output>docs/api</output>
    </paths>
    <version number="latest">
        <api>
            <source dsn=".">
                <path>src</path>
            </source>
        </api>
    </version>
</phpdocumentor>
```

### Tasks
- [ ] Install phpDocumentor
- [ ] Configure documentation generation
- [ ] Generate HTML documentation
- [ ] Host on GitHub Pages or Read the Docs
- [ ] Add documentation badge to README
- [ ] Automate doc generation in CI

---

## 4.3 Add Performance Optimizations

**Priority**: 🟡 Medium
**Effort**: 4 hours
**Impact**: Performance, scalability

### Response Caching

```php
namespace MLflow\Cache;

use Psr\SimpleCache\CacheInterface;

final class CachingMLflowClient extends MLflowClient
{
    public function __construct(
        string $trackingUri,
        MLflowConfig|array $config = [],
        ?LoggerInterface $logger = null,
        private readonly ?CacheInterface $cache = null,
        private readonly int $cacheTtl = 300, // 5 minutes
    ) {
        parent::__construct($trackingUri, $config, $logger);
    }

    public function experiments(): ExperimentApi
    {
        if ($this->cache !== null) {
            return new CachedExperimentApi(
                $this->getHttpClient(),
                $this->logger,
                $this->cache,
                $this->cacheTtl
            );
        }

        return parent::experiments();
    }
}

final class CachedExperimentApi extends ExperimentApi
{
    public function __construct(
        ClientInterface $client,
        LoggerInterface $logger,
        private readonly CacheInterface $cache,
        private readonly int $ttl,
    ) {
        parent::__construct($client, $logger);
    }

    public function getById(string $experimentId): Experiment
    {
        $cacheKey = "experiment:{$experimentId}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $experiment = parent::getById($experimentId);
        $this->cache->set($cacheKey, $experiment, $this->ttl);

        return $experiment;
    }
}
```

### Connection Pooling

Already handled by Guzzle's keep-alive, but document best practices:

```php
// Reuse client for better performance
$client = new MLflowClient('http://localhost:5000', [
    'headers' => [
        'Connection' => 'keep-alive',
    ],
]);

// Bad: creates new connection each time
for ($i = 0; $i < 100; $i++) {
    $tempClient = new MLflowClient('http://localhost:5000');
    $tempClient->experiments()->getById($expId);
}

// Good: reuses connection
for ($i = 0; $i < 100; $i++) {
    $client->experiments()->getById($expId);
}
```

### Tasks
- [ ] Add optional PSR-16 cache support
- [ ] Create CachingMLflowClient decorator
- [ ] Add cache invalidation strategies
- [ ] Document performance best practices
- [ ] Add benchmarks
- [ ] Add cache tests

---

## 4.4 Security Hardening

**Priority**: 🔴 High
**Effort**: 3 hours
**Impact**: Security

### Path Traversal Protection

```php
namespace MLflow\Util;

use MLflow\Exception\InvalidArgumentException;

final class SecurityHelper
{
    /**
     * Validate and sanitize file path to prevent traversal attacks
     */
    public static function validatePath(string $path, string $baseDir): string
    {
        // Normalize path
        $path = str_replace(['\\', '//'], '/', $path);
        $realPath = realpath($baseDir . '/' . $path);

        // Ensure path is within base directory
        if ($realPath === false || !str_starts_with($realPath, realpath($baseDir))) {
            throw new InvalidArgumentException(
                "Invalid path: path traversal detected"
            );
        }

        return $realPath;
    }

    /**
     * Sanitize experiment/run name
     */
    public static function sanitizeName(string $name): string
    {
        // Remove control characters and limit length
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name);

        if (strlen($name) > 255) {
            throw new InvalidArgumentException(
                "Name too long: maximum 255 characters"
            );
        }

        if ($name === '') {
            throw new InvalidArgumentException("Name cannot be empty");
        }

        return $name;
    }

    /**
     * Validate tag key
     */
    public static function validateTagKey(string $key): string
    {
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $key)) {
            throw new InvalidArgumentException(
                "Invalid tag key: must contain only alphanumeric, underscore, hyphen, or dot"
            );
        }

        if (strlen($key) > 250) {
            throw new InvalidArgumentException(
                "Tag key too long: maximum 250 characters"
            );
        }

        return $key;
    }
}
```

### Sensitive Data Masking

```php
// In BaseApi
protected function logRequest(string $method, string $uri, array $options): void
{
    // Mask sensitive headers
    $safeOptions = $options;

    if (isset($safeOptions['headers'])) {
        foreach ($safeOptions['headers'] as $key => $value) {
            if (in_array(strtolower($key), ['authorization', 'api-key', 'token'])) {
                $safeOptions['headers'][$key] = '***REDACTED***';
            }
        }
    }

    $this->logger->debug("MLflow API Request: {$method} {$uri}", $safeOptions);
}
```

### Tasks
- [ ] Add SecurityHelper utility
- [ ] Add path validation in ArtifactApi
- [ ] Add input sanitization in all API methods
- [ ] Add sensitive data masking in logs
- [ ] Add rate limiting (optional)
- [ ] Security audit
- [ ] Add security documentation

---

## 4.5 Add Mutation Testing

**Priority**: 🟡 Medium
**Effort**: 2 hours
**Impact**: Test quality

### Setup Infection

```json
// composer.json
{
    "require-dev": {
        "infection/infection": "^0.27"
    }
}
```

```json
// infection.json.dist
{
    "$schema": "vendor/infection/infection/resources/schema.json",
    "source": {
        "directories": ["src"]
    },
    "logs": {
        "text": "infection.log",
        "badge": {
            "branch": "main"
        }
    },
    "mutators": {
        "@default": true
    },
    "minMsi": 80,
    "minCoveredMsi": 85
}
```

### Tasks
- [ ] Install Infection
- [ ] Configure mutation testing
- [ ] Run baseline mutation tests
- [ ] Improve test suite based on survivors
- [ ] Add to CI pipeline
- [ ] Add badge to README

---

## Additional Improvements (Backlog)

### Documentation
- [ ] Add cookbook/recipes section
- [ ] Add troubleshooting guide
- [ ] Add migration guide for deprecated APIs
- [ ] Add architecture decision records (ADRs)
- [ ] Add contributing guide

### Testing
- [ ] Add performance benchmarks with PHPBench
- [ ] Add contract tests
- [ ] Add test fixtures/factories
- [ ] Increase code coverage to 95%+

### Architecture
- [ ] Add PSR-15 middleware pipeline
- [ ] Add PSR-14 event system
- [ ] Extract API version handling
- [ ] Add resource manager for file handles

### Performance
- [ ] Optimize collection operations with generators
- [ ] Add lazy loading for large datasets
- [ ] Add streaming support for large artifacts

### Developer Experience
- [ ] Add debugging utilities
- [ ] Add retry mechanism with exponential backoff
- [ ] Improve error messages with suggestions
- [ ] Add IDE helper generator
- [ ] Create Rector rules for upgrades
- [ ] Add PHPStan extension

### Packaging
- [ ] GitHub Actions workflows
- [ ] Automated releases
- [ ] Changelog generation

---

## Progress Tracking

### Phase 1: Foundation ✅
- [x] 1.1 Eliminate formatTags() duplication
- [x] 1.2 Create exception hierarchy
- [x] 1.3 Fix Webhook immutability
- [x] 1.4 Fix Experiment tags
- [x] 1.5 Add validation in fromArray()
- [x] 1.6 Create constant classes

**Progress**: 6/6 (100%) ✅ **COMPLETE**

### Phase 2: Type Safety ✅
- [x] 2.1 Create MLflowConfig value object
- [x] 2.2 Add interfaces
- [x] 2.3 Fix ModelApi return types (deprecated in favor of ModelRegistryApi)
- [x] 2.4 Create WebhookStatus enum (completed in Phase 1.3)
- [x] 2.6 Add response validation
- [x] 2.7 Add ArrayAccess to MetricCollection

**Progress**: 6/6 (100%) ✅ **COMPLETE**

### Phase 3: Developer Experience ✅❌
- [x] 3.1 Add builder classes
- [ ] 3.2 Complete collection APIs
- [ ] 3.3 Add connection validation
- [ ] 3.4 Add usage examples in docblocks
- [ ] 3.5 Create factory methods

**Progress**: 1/5 (20%)

### Phase 4: Polish ✅❌
- [ ] 4.1 Add integration test suite
- [ ] 4.2 Generate API documentation
- [ ] 4.3 Add performance optimizations
- [ ] 4.4 Security hardening
- [ ] 4.5 Add mutation testing

**Progress**: 0/5 (0%)

---

## Notes

- Each phase builds on previous phases
- Phases can be tackled in separate sessions
- Mark items complete as you go
- Update progress percentages
- Add notes about challenges or deviations
- Document any breaking changes

---

**Last Updated**: 2026-02-11
**Status**: Ready to begin Phase 1
