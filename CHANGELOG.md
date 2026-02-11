# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-02-11

### Added

#### Phase 1: Foundation
- **Exception Hierarchy**: 10 specific exception types mapped to HTTP status codes
  - `NotFoundException` (404)
  - `AuthenticationException` (401, 403)
  - `ValidationException` (400, 422)
  - `RateLimitException` (429)
  - `TimeoutException` (408, 504)
  - `ConflictException` (409)
  - `NetworkException` (connection errors)
  - `ApiException` (general API errors)
  - `ConfigurationException` (client configuration errors)
  - `InvalidArgumentException` (invalid inputs)
- **Code Deduplication**: Extracted `formatTags()` method to `BaseApi` (removed from 4 locations)
- **Immutability**: Converted `Webhook` model to readonly class with `WebhookStatus` enum
- **Typed Collections**: Replaced primitive arrays with `TagCollection<ExperimentTag>` in `Experiment` model
- **Validation Helper**: `ValidationHelper` utility for consistent `fromArray()` validation
- **Constants**: Created `HttpStatus`, `MLflowEndpoints`, `MLflowFields` constant classes

#### Phase 2: Type Safety
- **Value Objects**: `MLflowConfig` value object with constructor validation
- **Interfaces**: Added `ModelInterface`, `SerializableModelInterface`, `CollectionInterface`, `ApiInterface`
- **Response Validation**: `ResponseValidator` utility for type-safe API response parsing
- **Deprecation**: Deprecated `ModelApi` in favor of `ModelRegistryApi`
- **ArrayAccess**: Implemented `ArrayAccess` interface on `MetricCollection`

#### Phase 3: Developer Experience
- **Fluent Builders**:
  - `RunBuilder` - Fluent API for creating and configuring runs
  - `ExperimentBuilder` - Fluent API for creating experiments
  - `ModelBuilder` - Fluent API for creating registered models
  - `TraceBuilder` - Fluent API for creating traces
- **Factory Methods**:
  - `Metric::now()`, `Metric::atTimestamp()`, `Metric::atTime()`
  - `Param::create()`
  - `RunTag::create()`, `ExperimentTag::create()`
- **Connection Validation**:
  - `MLflowClient::validateConnection()` - Validate server connectivity
  - `MLflowClient::getServerInfo()` - Get server version and status
- **Enhanced Collections**:
  - `merge()`, `reduce()`, `filter()` methods on all collections
  - `MetricCollection::filterByKeyPrefix()`, `filterByStepRange()`, `filterByValueRange()`, `uniqueByKey()`
  - `ParameterCollection::filterByKeyPrefix()`
  - `TagCollection::filterByKeyPrefix()`

#### Phase 4: Polish
- **Integration Tests**:
  - Docker Compose setup with MLflow server
  - `IntegrationTestCase` base class with auto-cleanup
  - `RunLifecycleTest` - Complete run operations
  - `ExperimentLifecycleTest` - Experiment CRUD and search
  - Comprehensive README for integration testing
- **API Documentation**:
  - phpDocumentor 3.x configuration (`phpdoc.xml`)
  - `composer docs` command to generate documentation
  - Output to `docs/api/` directory
- **Performance Optimizations**:
  - PSR-16 SimpleCache interface support
  - `CachingMLflowClient` wrapper with configurable TTL
  - `CachedExperimentApi` with smart cache invalidation
  - Automatic cache invalidation on write operations
- **Security Hardening**:
  - `SecurityHelper` utility class with comprehensive validation:
    - `validatePath()` - Path traversal protection
    - `sanitizeName()` - Name sanitization (max 255 chars, no control characters)
    - `validateTagKey()` - Tag key validation (alphanumeric + _-./, max 250 chars)
    - `validateTagValue()` - Tag value validation (max 5000 chars)
    - `validateMetricKey()` - Metric/parameter key validation
    - `validateExperimentId()` - Experiment ID format validation (numeric)
    - `validateRunId()` - Run ID format validation (32-char hex)
    - `maskSensitiveHeaders()` - Mask sensitive headers in logs
  - Automatic sensitive header masking in `BaseApi` logging
  - All validations follow MLflow API specifications
- **Mutation Testing**:
  - Infection 0.29 configuration (`infection.json.dist`)
  - Target MSI: 70%, Covered MSI: 80%
  - `composer mutation` command
  - Excludes function signatures and ignores assertions/exceptions

### Changed

- **BREAKING**: Minimum PHP version now 8.4+ (was 8.0+)
- **BREAKING**: `Experiment::$tags` is now `TagCollection<ExperimentTag>` (was `array`)
- **BREAKING**: `Webhook` converted to readonly class (properties no longer mutable)
- **BREAKING**: `MLflowClient` constructor now accepts `MLflowConfig|array` for config (array still supported for backward compatibility)
- **BREAKING**: All models converted to readonly classes (immutable by default)
- **BREAKING**: Enums used for status/stage values (`RunStatus`, `ModelStage`, `ViewType`, `LifecycleStage`, `WebhookStatus`)
- **Protected Logger**: Changed `MLflowClient::$logger` from private to protected for subclass access
- **Protected HTTP Client**: Added `MLflowClient::getHttpClient()` protected method for subclass access
- **Code Style**: All files now PSR-12 compliant with zero violations
- **Test Names**: Integration test methods use camelCase (PSR-12 compliant)

### Fixed

- Fixed duplicate `getTrackingUri()` method in `MLflowClient`
- Fixed method name inconsistencies in `ExperimentApi` (`updateExperiment` → `update`, `restoreExperiment` → `restore`)
- Fixed missing newlines at end of 41 files
- Fixed exception class definitions to PSR-12 standard
- Fixed collection interface return type variance issues

### Deprecated

- `ModelApi` - Use `ModelRegistryApi` instead (will be removed in v3.0)

## [1.0.0] - Initial Release

### Added

- Complete MLflow REST API implementation
- Full Model Registry support
- Artifact management (upload/download)
- Batch operations for metrics, parameters, tags
- Comprehensive unit test coverage
- PSR-3 logging support
- PSR-4 autoloading
- PSR-12 coding standards
- Type safety with PHP 8.0+ type hints
- Guzzle HTTP client integration

---

## Upgrade Guide

### Upgrading from 1.x to 2.0

#### PHP Version Requirement

PHP 8.4+ is now required. Update your `composer.json`:

```json
{
    "require": {
        "php": "^8.4"
    }
}
```

#### Experiment Tags

The `Experiment::$tags` property is now a `TagCollection`:

```php
// Before (1.x)
$tags = $experiment->getTags(); // array
foreach ($tags as $tag) {
    echo $tag['key'] . ': ' . $tag['value'];
}

// After (2.0)
$tags = $experiment->tags; // TagCollection<ExperimentTag>
foreach ($tags as $tag) {
    echo $tag->key . ': ' . $tag->value;
}
```

#### Enums for Status Values

Use enums instead of strings:

```php
use MLflow\Enum\{RunStatus, ModelStage};

// Before (1.x)
$client->runs()->setTerminated($runId, 'FINISHED');

// After (2.0)
$client->runs()->setTerminated($runId, RunStatus::FINISHED);

// Before (1.x)
$registry->transitionModelVersionStage($name, $version, 'Production', true);

// After (2.0)
$registry->transitionModelVersionStage(
    $name,
    $version,
    ModelStage::PRODUCTION,
    true
);
```

#### Immutable Models

All models are now readonly. Create new instances instead of modifying:

```php
// Before (1.x)
$webhook->setHttpUrl('https://new-url.com'); // No longer possible

// After (2.0)
// Models are immutable - retrieve fresh from API
$webhook = $client->webhooks()->get($webhookId);
```

#### Configuration

Use `MLflowConfig` for type-safe configuration:

```php
use MLflow\Config\MLflowConfig;

// New typed configuration
$config = new MLflowConfig(
    timeout: 60.0,
    headers: ['Authorization' => 'Bearer token'],
    verify: true
);

$client = new MLflowClient('http://localhost:5000', $config);

// Legacy array config still works
$client = new MLflowClient('http://localhost:5000', [
    'timeout' => 60,
    'headers' => ['Authorization' => 'Bearer token']
]);
```

#### Model Registry API

Replace `ModelApi` with `ModelRegistryApi`:

```php
// Before (1.x)
$registry = $client->models();

// After (2.0)
$registry = $client->modelRegistry();
```

---

[2.0.0]: https://github.com/axyr/mlflow-php-client/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/axyr/mlflow-php-client/releases/tag/v1.0.0
