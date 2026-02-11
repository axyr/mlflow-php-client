# MLflow PHP Client

A complete, modern, fully-tested PHP client library for MLflow REST API with PHP 8.4+ features, comprehensive type safety, and developer-friendly APIs.

[![Tests](https://img.shields.io/badge/tests-passing-brightgreen)]()
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-level%209-brightgreen)]()
[![PHP 8.4+](https://img.shields.io/badge/PHP-8.4+-blue)]()
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

## Features

✅ **Complete API Coverage** - All MLflow REST API endpoints implemented
✅ **Modern PHP 8.4** - Readonly classes, enums, named parameters, union types
✅ **Fluent Builders** - Intuitive RunBuilder, ExperimentBuilder, ModelBuilder APIs
✅ **Type Safety** - PHPStan level 9, full type hints, typed collections
✅ **PSR Compliance** - PSR-3 logging, PSR-4 autoloading, PSR-12 coding standards, PSR-16 caching
✅ **Security Hardening** - Input validation, path traversal protection, sensitive data masking
✅ **Performance** - Optional PSR-16 caching, connection pooling, batch operations
✅ **Developer Experience** - Factory methods, connection validation, comprehensive exceptions
✅ **Comprehensive Testing** - 55+ unit tests, integration tests, mutation testing ready

## Installation

```bash
composer require martijn/mlflow-php-client
```

### Laravel Installation

Laravel integration is **included**! The package will auto-register via package discovery.

```bash
# 1. Install package
composer require martijn/mlflow-php-client

# 2. Publish config (optional)
php artisan vendor:publish --tag=mlflow-config

# 3. Configure via .env
MLFLOW_TRACKING_URI=http://localhost:5000
MLFLOW_API_TOKEN=your-token-here
```

That's it! The ServiceProvider and Facade are auto-discovered.

## Requirements

- PHP 8.4 or higher
- MLflow server (2.0+)
- Composer
- Laravel 10+ or 11+ (optional, for Laravel integration)

## Quick Start

### Standalone PHP

```php
use MLflow\MLflowClient;

// Initialize the client
$client = new MLflowClient('http://localhost:5000');

// Validate connection
if ($client->validateConnection()) {
    echo "Connected to MLflow!\n";
}

// Create an experiment
$experiment = $client->experiments()->create('my-experiment');

// Create a run using the fluent builder
$run = $client->createRunBuilder($experiment->experimentId)
    ->withName('training-run-001')
    ->withParam('learning_rate', '0.01')
    ->withParam('batch_size', '32')
    ->withMetric('accuracy', 0.95, step: 1)
    ->withMetric('loss', 0.05, step: 1)
    ->withTag('model_type', 'neural_network')
    ->withTag('framework', 'tensorflow')
    ->start();

echo "Run created: {$run->info->runId}\n";
```

### Laravel Quick Start

```php
use MLflow\Laravel\Facades\MLflow;

// Use the Facade
$experiment = MLflow::experiments()->create('my-experiment');

$run = MLflow::createRunBuilder($experiment->experimentId)
    ->withName('training-run-001')
    ->withParam('learning_rate', '0.01')
    ->withMetric('accuracy', 0.95)
    ->start();

// Or use dependency injection
class MLTrainingController extends Controller
{
    public function __construct(
        private \MLflow\MLflowClient $mlflow
    ) {}

    public function train(Request $request)
    {
        $run = $this->mlflow->createRunBuilder($request->experiment_id)
            ->withName('laravel-training-' . now())
            ->start();

        // Training logic...

        return response()->json(['run_id' => $run->info->runId]);
    }
}

// Or use helper functions
$client = mlflow();
mlflow_log_metric($runId, 'accuracy', 0.95);
mlflow_log_param($runId, 'learning_rate', '0.01');
```

## New Features in v2.0

### 🚀 Fluent Builders

Create experiments, runs, and models with intuitive builder APIs:

```php
// Run Builder
$run = $client->createRunBuilder($experimentId)
    ->withName('training-001')
    ->withParam('lr', '0.01')
    ->withMetric('accuracy', 0.95)
    ->withTag('version', 'v1.0')
    ->start();

// Experiment Builder
$experiment = $client->createExperimentBuilder('my-experiment')
    ->withArtifactLocation('s3://bucket/path')
    ->withTag('team', 'ml-team')
    ->withTag('project', 'classification')
    ->create();

// Model Builder
$model = $client->createModelBuilder('my-model')
    ->withDescription('Image classification model')
    ->withTag('framework', 'pytorch')
    ->withTag('task', 'classification')
    ->create();
```

### ⚡ Performance: PSR-16 Caching

Reduce API calls with optional caching:

```php
use MLflow\Cache\CachingMLflowClient;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$cache = new Psr16Cache(new FilesystemAdapter());

$client = new CachingMLflowClient(
    'http://localhost:5000',
    cache: $cache,
    cacheTtl: 300  // 5 minutes
);

// First call hits MLflow server
$exp = $client->experiments()->getById('123');

// Second call uses cached response
$exp = $client->experiments()->getById('123');  // Instant!
```

### 🔒 Security Features

Built-in security validations and sensitive data masking:

```php
use MLflow\Util\SecurityHelper;

// Validate and sanitize inputs
$safeName = SecurityHelper::sanitizeName($userInput);
$safeTagKey = SecurityHelper::validateTagKey($tagKey);
$safeTagValue = SecurityHelper::validateTagValue($tagValue);

// Path traversal protection
$safePath = SecurityHelper::validatePath($userPath, $baseDir);

// Validate IDs
$experimentId = SecurityHelper::validateExperimentId($id);
$runId = SecurityHelper::validateRunId($id);

// Sensitive headers automatically masked in logs
// Authorization, API-Key, Token headers are redacted
```

### 🏗️ Type-Safe Configuration

Configure client with type-safe value object:

```php
use MLflow\Config\MLflowConfig;

$config = new MLflowConfig(
    timeout: 60.0,
    connectTimeout: 10,
    maxRetries: 3,
    retryDelay: 1.0,
    headers: ['Authorization' => 'Bearer token'],
    verify: true,  // SSL verification
    proxy: 'http://proxy.example.com:8080',
    debug: false
);

$client = new MLflowClient('http://localhost:5000', $config);

// Or use array for backward compatibility
$client = new MLflowClient('http://localhost:5000', [
    'timeout' => 60,
    'headers' => ['Authorization' => 'Bearer token']
]);
```

### 🎯 Factory Methods

Create model instances easily:

```php
use MLflow\Model\{Metric, Param, RunTag, ExperimentTag};

// Create metrics with factory methods
$metric1 = Metric::now('accuracy', 0.95, step: 1);
$metric2 = Metric::atTimestamp('loss', 0.05, time(), step: 1);

// Create parameters and tags
$param = Param::create('learning_rate', '0.01');
$runTag = RunTag::create('version', 'v1.0');
$expTag = ExperimentTag::create('team', 'ml-team');
```

### 🔍 Enhanced Collections

Rich collection APIs with filtering and transformations:

```php
// Get metrics for a run
$run = $client->runs()->getById($runId);

// Filter metrics by key
$accuracyMetrics = $run->data->metrics->getByKey('accuracy');

// Filter by value range
$highAccuracy = $run->data->metrics->filterByValueRange(min: 0.9);

// Filter by step range
$earlyMetrics = $run->data->metrics->filterByStepRange(max: 10);

// Get unique metrics (deduplicate by key)
$uniqueMetrics = $run->data->metrics->uniqueByKey();

// Merge collections
$allMetrics = $metrics1->merge($metrics2);

// Map and reduce
$avgAccuracy = $accuracyMetrics->reduce(
    fn($carry, $m) => $carry + $m->value,
    0
) / $accuracyMetrics->count();
```

### ✅ Connection Validation

Validate MLflow server connectivity:

```php
// Simple validation
if ($client->validateConnection()) {
    echo "Connected!\n";
}

// Get detailed server info
$info = $client->getServerInfo();
echo "MLflow version: {$info['version']}\n";
echo "Reachable: " . ($info['reachable'] ? 'yes' : 'no') . "\n";

if (isset($info['error'])) {
    echo "Error: {$info['error']}\n";
}
```

### 🎯 Comprehensive Exception Hierarchy

Catch specific exceptions for better error handling:

```php
use MLflow\Exception\{
    NotFoundException,
    ValidationException,
    AuthenticationException,
    RateLimitException,
    TimeoutException,
    NetworkException,
    ConflictException
};

try {
    $exp = $client->experiments()->getById('invalid');
} catch (NotFoundException $e) {
    echo "Experiment not found\n";
} catch (ValidationException $e) {
    echo "Invalid input: {$e->getMessage()}\n";
} catch (AuthenticationException $e) {
    echo "Authentication failed\n";
} catch (TimeoutException $e) {
    echo "Request timed out\n";
} catch (NetworkException $e) {
    echo "Network error: {$e->getMessage()}\n";
}
```

## API Documentation

### Client Initialization

```php
$client = new MLflowClient(
    trackingUri: 'http://localhost:5000',
    config: [
        'timeout' => 60,
        'headers' => ['Authorization' => 'Bearer token']
    ],
    logger: $psrLogger // Optional PSR-3 logger
);
```

### Experiments API

```php
// Create experiment
$experiment = $client->experiments()->create(
    name: 'experiment-name',
    artifactLocation: 's3://bucket/path',
    tags: ['team' => 'ml-team']
);

// Search experiments
$results = $client->experiments()->search(
    filterString: "attribute.name = 'my-experiment'",
    maxResults: 100,
    orderBy: ['creation_time DESC']
);

// Get experiment
$experiment = $client->experiments()->getById('experiment-id');
$experiment = $client->experiments()->getByName('experiment-name');

// Update/Delete/Restore
$client->experiments()->update('exp-id', 'new-name');
$client->experiments()->deleteExperiment('exp-id');
$client->experiments()->restore('exp-id');

// Tags
$client->experiments()->setTag('exp-id', 'key', 'value');
$client->experiments()->deleteTag('exp-id', 'key');
```

### Runs API

```php
use MLflow\Enum\RunStatus;

// Create run
$run = $client->runs()->create(
    experimentId: 'exp-id',
    userId: 'user@example.com',
    runName: 'training-run',
    tags: ['version' => 'v1.0']
);

// Search runs
$results = $client->runs()->search(
    experimentIds: ['exp1', 'exp2'],
    filter: 'metrics.accuracy > 0.9',
    maxResults: 100,
    orderBy: ['metrics.accuracy DESC']
);

// Log metrics
$client->runs()->logMetric($runId, 'accuracy', 0.95, $timestamp, $step);

// Log parameters
$client->runs()->logParameter($runId, 'learning_rate', '0.01');

// Batch logging (most efficient)
$client->runs()->logBatch(
    runId: $runId,
    metrics: [
        ['key' => 'accuracy', 'value' => 0.95, 'step' => 1],
        ['key' => 'loss', 'value' => 0.05, 'step' => 1]
    ],
    params: ['lr' => '0.01', 'batch_size' => '32'],
    tags: ['stage' => 'training']
);

// Update run status (using enum)
$client->runs()->setTerminated($runId, RunStatus::FINISHED);

// Delete/Restore runs
$client->runs()->deleteRun($runId);
$client->runs()->restoreRun($runId);
```

### Model Registry API

```php
use MLflow\Enum\ModelStage;

$registry = $client->modelRegistry();

// Create registered model
$model = $registry->createRegisteredModel(
    name: 'my-model',
    description: 'Image classification model',
    tags: ['framework' => 'pytorch']
);

// Create model version
$version = $registry->createModelVersion(
    name: 'my-model',
    source: 's3://bucket/path/to/model',
    runId: $runId,
    description: 'Initial version'
);

// Transition model stage (using enum)
$registry->transitionModelVersionStage(
    name: 'my-model',
    version: '1',
    stage: ModelStage::PRODUCTION,
    archiveExistingVersions: true
);

// Search models
$results = $registry->searchRegisteredModels(
    filter: "name = 'my-model'",
    maxResults: 100
);

// Get latest versions
$versions = $registry->getLatestVersions(
    name: 'my-model',
    stages: [ModelStage::PRODUCTION, ModelStage::STAGING]
);

// Model aliases
$registry->setRegisteredModelAlias('my-model', 'champion', '1');
$version = $registry->getModelVersionByAlias('my-model', 'champion');
$registry->deleteRegisteredModelAlias('my-model', 'champion');

// Tags
$registry->setRegisteredModelTag('my-model', 'key', 'value');
$registry->setModelVersionTag('my-model', '1', 'key', 'value');
```

### Artifacts API

```php
// List artifacts
$artifacts = $client->artifacts()->list($runId, 'models/');

// Upload artifacts
$client->artifacts()->logArtifact(
    runId: $runId,
    localPath: '/path/to/model.pkl',
    artifactPath: 'models'
);

// Upload directory
$client->artifacts()->logArtifacts(
    runId: $runId,
    localDir: '/path/to/artifacts/',
    artifactPath: 'outputs'
);

// Download artifacts
$client->artifacts()->download(
    runId: $runId,
    artifactPath: 'models/model.pkl',
    dstPath: '/local/path/'
);
```

## Model Classes

Strongly-typed readonly model classes:

- `Experiment` - Experiment with TagCollection
- `Run` - Run with nested RunInfo and RunData
- `RunInfo` - Run metadata (ID, status, timestamps)
- `RunData` - Run data with typed collections
- `Metric` - Metric with value, timestamp, step
- `Param` - Parameter key-value
- `RunTag`, `ExperimentTag`, `ModelTag` - Typed tags
- `RegisteredModel` - Model registry entry
- `ModelVersion` - Model version with stage enum
- `FileInfo` - Artifact file information

## Advanced Usage

### Pagination

```php
$pageToken = null;
$allExperiments = [];

do {
    $result = $client->experiments()->search(
        maxResults: 100,
        pageToken: $pageToken
    );

    $allExperiments = array_merge($allExperiments, $result['experiments']);
    $pageToken = $result['next_page_token'];
} while ($pageToken !== null);
```

### Logging with PSR-3

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('mlflow');
$logger->pushHandler(new StreamHandler('mlflow.log', Logger::DEBUG));

$client = new MLflowClient(
    trackingUri: 'http://localhost:5000',
    logger: $logger
);

// All API requests/responses are automatically logged
// Sensitive headers (Authorization, API-Key) are masked
```

## Testing

```bash
# Run unit tests
composer test

# Run integration tests (requires Docker)
docker-compose -f docker-compose.test.yml up --abort-on-container-exit

# Run mutation testing
composer mutation

# Static analysis (PHPStan level 9)
composer phpstan

# Code style check (PSR-12)
composer cs-check

# Fix code style
composer cs-fix

# Generate API documentation
composer docs

# Run ALL CI/CD checks locally (recommended before pushing)
./bin/ci-check
```

### Pre-commit Hook

Install the pre-commit hook to automatically run checks before each commit:

```bash
cp hooks/pre-commit .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

This ensures:
- PHPStan passes (level 9)
- Code style is PSR-12 compliant
- All unit tests pass

**Benefits:**
- Catch issues before CI/CD
- Faster feedback loop
- Prevent broken commits

## Documentation

- **API Docs**: Run `composer docs` to generate full API documentation
- **Integration Tests**: See `tests/Integration/README.md`
- **MLflow REST API**: https://mlflow.org/docs/latest/rest-api.html

## Supported MLflow Versions

Tested with MLflow 2.0+, 2.3+, 2.8+, 2.10+

## Architecture

Built with modern PHP 8.4 features:
- **Readonly Classes**: Immutable domain models
- **Enums**: Type-safe status and stage values (RunStatus, ModelStage, ViewType, LifecycleStage, WebhookStatus)
- **Named Parameters**: Clear, self-documenting API calls
- **Union Types**: Flexible configuration (MLflowConfig|array)
- **Typed Collections**: Generic collections with PHPDoc templates
- **Constructor Property Promotion**: Concise, clean code

## Contributing

Contributions welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Write tests (maintain 100% coverage)
4. Ensure PHPStan level 9 passes
5. Follow PSR-12 coding standards
6. Submit a pull request

## License

MIT License - see LICENSE file for details

## Support

- **Issues**: https://github.com/axyr/mlflow-php-client/issues
- **MLflow Docs**: https://mlflow.org
- **PHP**: 8.4+ required

## Credits

Created by Martijn ([Axyr Media](https://axyrmedia.nl))

A complete, modern PHP implementation of the MLflow REST API with comprehensive type safety, security hardening, and developer-friendly features.
