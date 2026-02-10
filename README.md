# MLflow PHP Client

A complete, fully-tested PHP client library for MLflow REST API, implementing all endpoints from the official MLflow documentation.

## Features

✅ **Complete API Coverage** - All MLflow REST API endpoints implemented
✅ **Full Model Registry Support** - Complete model versioning and stage management
✅ **Comprehensive Testing** - 100% endpoint coverage with unit tests
✅ **Type Safety** - Full PHP 8+ type hints and return types
✅ **PSR Compliance** - PSR-3 logging, PSR-4 autoloading, PSR-12 coding standards
✅ **Artifact Management** - Upload/download artifacts with multiple storage backends
✅ **Batch Operations** - Efficient batch logging of metrics, parameters, and tags

## Installation

```bash
composer require martijn/mlflow-php-client
```

## Requirements

- PHP 8.0 or higher
- MLflow server running (local or remote)
- Composer for dependency management

## Quick Start

```php
use MLflow\MLflowClient;

// Initialize the client
$client = new MLflowClient('http://localhost:5000');

// Create an experiment
$experiment = $client->experiments()->create('my-experiment');

// Create a run
$run = $client->runs()->create(
    experimentId: $experiment->getExperimentId(),
    runName: 'training-run-001'
);

// Log metrics, parameters, and tags
$client->runs()->logBatch(
    runId: $run->getRunId(),
    metrics: [
        ['key' => 'accuracy', 'value' => 0.95, 'step' => 1],
        ['key' => 'loss', 'value' => 0.05, 'step' => 1]
    ],
    params: [
        'learning_rate' => '0.01',
        'batch_size' => '32'
    ],
    tags: [
        'model_type' => 'neural_network',
        'framework' => 'tensorflow'
    ]
);

// Complete the run
$client->runs()->setTerminated($run->getRunId(), 'FINISHED');
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
    filter: "attribute.name = 'my-experiment'",
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
    runViewType: RunApi::VIEW_TYPE_ACTIVE_ONLY,
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

// Update run status
$client->runs()->update($runId, 'FINISHED', $endTime);
$client->runs()->setTerminated($runId, RunApi::STATUS_FINISHED);

// Delete/Restore runs
$client->runs()->deleteRun($runId);
$client->runs()->restore($runId);
```

### Model Registry API

```php
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

// Transition model stage
$registry->transitionModelVersionStage(
    name: 'my-model',
    version: '1',
    stage: ModelRegistryApi::STAGE_PRODUCTION,
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
    stages: ['Production', 'Staging']
);

// Model aliases
$registry->setRegisteredModelAlias('my-model', 'champion', '1');
$version = $registry->getModelVersionByAlias('my-model', 'champion');
$registry->deleteRegisteredModelAlias('my-model', 'champion');

// Tags
$registry->setRegisteredModelTag('my-model', 'key', 'value');
$registry->setModelVersionTag('my-model', '1', 'key', 'value');
```

### Metrics API

```php
// Get metric history
$history = $client->metrics()->getHistory($runId, 'accuracy');

// Get history for multiple metrics
$histories = $client->metrics()->getHistoryBulk(
    runId: $runId,
    metricKeys: ['accuracy', 'loss', 'f1_score']
);

// Process metric history
foreach ($history as $metric) {
    echo sprintf(
        "Step %d: %.4f at %s\n",
        $metric->getStep(),
        $metric->getValue(),
        date('Y-m-d H:i:s', $metric->getTimestamp() / 1000)
    );
}
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

// Get artifact URI
$uri = $client->artifacts()->getDownloadUri($runId, 'models/');
```

## Model Classes

The client provides strongly-typed model classes for all MLflow entities:

- `Experiment` - Experiment metadata and configuration
- `Run` - Run information with nested RunInfo and RunData
- `RunInfo` - Run metadata (ID, status, timestamps)
- `RunData` - Run data (metrics, params, tags)
- `Metric` - Individual metric with value, timestamp, and step
- `Param` - Key-value parameter
- `RunTag` - Key-value tag
- `RegisteredModel` - Model registry entry
- `ModelVersion` - Specific model version
- `FileInfo` - Artifact file information

## Advanced Usage

### Custom HTTP Client

```php
use GuzzleHttp\Client;

$httpClient = new Client([
    'base_uri' => 'http://mlflow.example.com/api/2.0/',
    'timeout' => 120,
    'headers' => [
        'Authorization' => 'Bearer your-token',
        'X-Custom-Header' => 'value'
    ]
]);

$client = new MLflowClient('http://mlflow.example.com');
$client->setHttpClient($httpClient);
```

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

### Error Handling

```php
use MLflow\Exception\MLflowException;

try {
    $experiment = $client->experiments()->getByName('non-existent');
} catch (MLflowException $e) {
    echo "Error: " . $e->getMessage() . "\n";

    // Get additional context if available
    $context = $e->getContext();
    if ($context) {
        print_r($context);
    }
}
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
```

## Testing

Run the test suite:

```bash
# Run all tests
composer test

# Run with code coverage
composer test -- --coverage-html coverage

# Run specific test file
composer test tests/Api/ExperimentApiTest.php

# Static analysis
composer phpstan

# Code style check
composer cs-check

# Fix code style
composer cs-fix
```

## Supported MLflow Versions

This client supports MLflow REST API v2.0 and is tested with:
- MLflow 2.0+
- MLflow 2.3+
- MLflow 2.8+
- MLflow 2.10+

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Write tests for new functionality
4. Ensure all tests pass
5. Submit a pull request

## License

MIT License - see LICENSE file for details

## Support

- **Issues**: https://github.com/martijn/mlflow-php-client/issues
- **Documentation**: https://mlflow.org/docs/latest/rest-api.html
- **MLflow**: https://mlflow.org

## Credits

Created by Martijn - A complete PHP implementation of the MLflow REST API, providing full feature parity with the official Python and JavaScript clients.