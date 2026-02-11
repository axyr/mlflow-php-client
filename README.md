# MLflow PHP Client

A modern, fully-typed Laravel package for MLflow REST API with PHP 8.4+, comprehensive testing utilities, and Laravel-native developer experience.

[![Tests](https://img.shields.io/badge/tests-73%20passing-brightgreen)]()
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-level%209-brightgreen)]()
[![PHP 8.4+](https://img.shields.io/badge/PHP-8.4+-blue)]()
[![Laravel 12](https://img.shields.io/badge/Laravel-12-red)]()
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

## Features

- ✅ **Complete MLflow API** - All REST endpoints (Experiments, Runs, Models, Traces, Artifacts, Datasets, Prompts, Webhooks)
- ✅ **Laravel First** - Facades, Events, Commands, Testing fakes, Dependency Injection
- ✅ **Fluent Builders** - Intuitive RunBuilder, ExperimentBuilder, ModelBuilder, TraceBuilder
- ✅ **Testing Utilities** - `MLflow::fake()` with assertions for TDD
- ✅ **Type Safety** - PHPStan Level 9, full type hints, contracts/interfaces
- ✅ **Modern PHP 8.4** - Readonly classes, enums, named parameters
- ✅ **Production Ready** - Error handling, logging, caching, batch operations

## Installation

```bash
composer require martijn/mlflow-php-client
```

### Laravel Setup

```bash
# Install package
composer require martijn/mlflow-php-client

# Install (optional - auto-discovered)
php artisan mlflow:install

# Test connection
php artisan mlflow:test-connection
```

### Configuration

```env
MLFLOW_TRACKING_URI=http://localhost:5000
MLFLOW_API_TOKEN=your-token-here
MLFLOW_DEFAULT_EXPERIMENT=
```

## Quick Start

### Basic Usage

```php
use MLflow\Laravel\Facades\MLflow;

// Create experiment
$experiment = MLflow::experiments()->create('my-experiment');

// Create run with builder
$run = MLflow::createRunBuilder($experiment->experimentId)
    ->withName('training-run-001')
    ->withParam('learning_rate', '0.01')
    ->withMetric('accuracy', 0.95)
    ->withTag('model', 'transformer')
    ->start();

// Log more metrics
MLflow::runs()->logMetric($run->info->runId, 'loss', 0.05, step: 100);
```

### Dependency Injection

```php
use MLflow\Contracts\MLflowClientContract;
use MLflow\Contracts\RunApiContract;

class TrainingService
{
    public function __construct(
        private MLflowClientContract $mlflow,
        private RunApiContract $runs
    ) {}

    public function train(array $config): void
    {
        $run = $this->runs->create($this->experimentId);
        // Training logic...
    }
}
```

### Testing

```php
use MLflow\Laravel\Facades\MLflow;

public function test_model_training()
{
    MLflow::fake();

    // Your code that uses MLflow
    $this->service->train(['lr' => 0.01]);

    // Assert MLflow interactions
    MLflow::assertExperimentCreated('training');
    MLflow::assertMetricLogged($runId, 'accuracy', 0.95);
    MLflow::assertParamLogged($runId, 'learning_rate', '0.01');
}
```

### Events

```php
use MLflow\Laravel\Events\{RunStarted, MetricLogged, ModelRegistered};

Event::listen(RunStarted::class, function ($event) {
    Log::info('Run started', ['run_id' => $event->run->info->runId]);
});

Event::listen(MetricLogged::class, function ($event) {
    Metrics::gauge('mlflow.metric', $event->value, [
        'key' => $event->key,
    ]);
});
```

### Artisan Commands

```bash
# List experiments
php artisan mlflow:experiments:list

# List with filter
php artisan mlflow:experiments:list --filter="name LIKE '%prod%'" --max=20

# Clear cache
php artisan mlflow:clear-cache

# Generate IDE helper
php artisan mlflow:ide-helper
```

### Helper Functions

```php
// Get client instance
$client = mlflow();

// Get experiment
$experiment = mlflow_experiment('exp-123');

// Get run
$run = mlflow_run('run-456');

// Quick logging
mlflow_log_metric($runId, 'accuracy', 0.95);
mlflow_log_param($runId, 'lr', '0.01');
```

### Custom Macros

```php
use MLflow\Builder\RunBuilder;

// Define macro in AppServiceProvider
RunBuilder::macro('withDefaults', function() {
    return $this
        ->withTag('environment', config('app.env'))
        ->withTag('user', auth()->user()?->email ?? 'system');
});

// Use macro
$run = MLflow::createRunBuilder($experimentId)
    ->withDefaults()
    ->start();
```

## API Overview

```php
// Experiments
MLflow::experiments()->create('name')
MLflow::experiments()->getById('exp-123')
MLflow::experiments()->search($filter)

// Runs
MLflow::runs()->create($experimentId)
MLflow::runs()->logMetric($runId, 'accuracy', 0.95)
MLflow::runs()->logParameter($runId, 'lr', '0.01')
MLflow::runs()->logBatch($runId, $metrics, $params)

// Model Registry
MLflow::modelRegistry()->createRegisteredModel('model-name')
MLflow::modelRegistry()->createModelVersion('model', 's3://path')
MLflow::modelRegistry()->transitionModelVersionStage('model', '1', 'Production')

// Artifacts
MLflow::artifacts()->listArtifacts($runId)
MLflow::artifacts()->downloadArtifact($runId, 'model.pkl')

// Traces (LLM tracking)
MLflow::traces()->logTrace($traceData)
MLflow::traces()->searchTraces($experimentId)

// Datasets
MLflow::datasets()->createDataset('training-data')
MLflow::datasets()->searchDatasets($experimentId)

// Prompts (LLM)
MLflow::prompts()->createPrompt('prompt-name', 'template')
MLflow::prompts()->getPrompt('prompt-name')
```

## Documentation

- [Testing Guide](docs/TESTING.md) - Comprehensive testing with fakes and assertions
- [Integration Patterns](docs/INTEGRATION_PATTERNS.md) - Real-world Laravel integration examples
- [Best Practices](docs/BEST_PRACTICES.md) - Performance, security, and optimization tips
- [Troubleshooting](docs/TROUBLESHOOTING.md) - Common issues and solutions

## Requirements

- PHP 8.4+
- Laravel 12+
- MLflow server 2.0+

## Development

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run quality checks
composer quality

# Fix code style
composer pint

# Static analysis
composer phpstan
```

## Quality

- **73 tests** with 356 assertions
- **PHPStan Level 9** - Strictest static analysis
- **100% Laravel Pint** - PSR-12 compliant
- **Comprehensive coverage** - Unit, integration, and feature tests

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## Security

For security vulnerabilities, see [SECURITY.md](SECURITY.md).

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## License

MIT License. See [LICENSE](LICENSE) for details.

## Credits

Built with ❤️ for the Laravel and MLflow communities.
