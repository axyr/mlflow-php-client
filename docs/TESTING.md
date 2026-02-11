# Testing Guide

This guide shows you how to test your MLflow integration in Laravel applications using the provided testing utilities.

## Table of Contents

- [Quick Start](#quick-start)
- [Using the Fake](#using-the-fake)
- [Available Assertions](#available-assertions)
- [Complete Examples](#complete-examples)
- [Advanced Testing](#advanced-testing)

## Quick Start

The MLflow package provides a powerful testing fake that allows you to test your code without connecting to a real MLflow server.

### Basic Usage

```php
use MLflow\Laravel\Facades\MLflow;
use Tests\TestCase;

class ExperimentTest extends TestCase
{
    public function test_creates_experiment()
    {
        // Replace the real MLflow client with a fake
        MLflow::fake();

        // Your application code
        $experiment = MLflow::experiments()->create('test-experiment');

        // Assert that the experiment was created
        MLflow::assertExperimentCreated('test-experiment');
    }
}
```

## Using the Fake

### Setup in Tests

Always call `MLflow::fake()` at the beginning of your test:

```php
public function setUp(): void
{
    parent::setUp();
    MLflow::fake();
}
```

Or per test:

```php
public function test_logs_metrics()
{
    $fake = MLflow::fake();

    // Your test code...
}
```

### Accessing Recorded Data

The fake records all interactions and allows you to inspect them:

```php
$fake = MLflow::fake();

// Create some data
$experiment = MLflow::experiments()->create('my-experiment');
$run = MLflow::runs()->create($experiment->experimentId);
MLflow::runs()->logMetric($run->info->runId, 'accuracy', 0.95);

// Inspect recorded data
$experiments = $fake->getRecordedExperiments();
$runs = $fake->getRecordedRuns();
$metrics = $fake->getRecordedMetrics();

$this->assertCount(1, $experiments);
$this->assertCount(1, $runs);
$this->assertCount(1, $metrics);
```

## Available Assertions

### assertExperimentCreated

Assert that an experiment with a specific name was created:

```php
MLflow::fake();

MLflow::experiments()->create('my-experiment');

MLflow::assertExperimentCreated('my-experiment');
```

### assertRunStarted

Assert that a run was started for a specific experiment:

```php
MLflow::fake();

$experiment = MLflow::experiments()->create('my-experiment');
$run = MLflow::runs()->create($experiment->experimentId);

MLflow::assertRunStarted($experiment->experimentId);
```

### assertMetricLogged

Assert that a specific metric was logged:

```php
MLflow::fake();

$experiment = MLflow::experiments()->create('my-experiment');
$run = MLflow::runs()->create($experiment->experimentId);
MLflow::runs()->logMetric($run->info->runId, 'accuracy', 0.95);

// Assert metric exists (any value)
MLflow::assertMetricLogged($run->info->runId, 'accuracy');

// Assert metric with specific value
MLflow::assertMetricLogged($run->info->runId, 'accuracy', 0.95);
```

### assertParamLogged

Assert that a parameter was logged:

```php
MLflow::fake();

$experiment = MLflow::experiments()->create('my-experiment');
$run = MLflow::runs()->create($experiment->experimentId);
MLflow::runs()->logParameter($run->info->runId, 'learning_rate', '0.01');

// Assert param exists (any value)
MLflow::assertParamLogged($run->info->runId, 'learning_rate');

// Assert param with specific value
MLflow::assertParamLogged($run->info->runId, 'learning_rate', '0.01');
```

### assertModelRegistered

Assert that a model was registered:

```php
MLflow::fake();

MLflow::modelRegistry()->createRegisteredModel('my-model');

MLflow::assertModelRegistered('my-model');
```

### assertNothingLogged

Assert that no MLflow operations were performed:

```php
MLflow::fake();

// Some code that shouldn't use MLflow

MLflow::assertNothingLogged();
```

## Complete Examples

### Testing a Training Service

```php
namespace Tests\Feature;

use App\Services\ModelTrainingService;
use MLflow\Laravel\Facades\MLflow;
use Tests\TestCase;

class ModelTrainingServiceTest extends TestCase
{
    private ModelTrainingService $service;

    public function setUp(): void
    {
        parent::setUp();
        MLflow::fake();
        $this->service = new ModelTrainingService();
    }

    public function test_tracks_training_metrics()
    {
        $this->service->train([
            'learning_rate' => 0.01,
            'batch_size' => 32,
        ]);

        // Assert experiment was created
        MLflow::assertExperimentCreated('model-training');

        // Get the created run
        $runs = MLflow::getRecordedRuns();
        $this->assertCount(1, $runs);

        $runId = $runs[0]['run_id'];

        // Assert parameters were logged
        MLflow::assertParamLogged($runId, 'learning_rate', '0.01');
        MLflow::assertParamLogged($runId, 'batch_size', '32');

        // Assert metrics were logged
        MLflow::assertMetricLogged($runId, 'training_loss');
        MLflow::assertMetricLogged($runId, 'validation_accuracy');
    }

    public function test_registers_model_after_training()
    {
        $this->service->trainAndRegister('my-model', [
            'learning_rate' => 0.01,
        ]);

        MLflow::assertModelRegistered('my-model');
    }
}
```

### Testing a Job

```php
namespace Tests\Feature;

use App\Jobs\TrainModelJob;
use MLflow\Laravel\Facades\MLflow;
use Tests\TestCase;

class TrainModelJobTest extends TestCase
{
    public function test_job_logs_to_mlflow()
    {
        MLflow::fake();

        $job = new TrainModelJob([
            'model_type' => 'classifier',
            'learning_rate' => 0.001,
        ]);

        $job->handle();

        MLflow::assertExperimentCreated('automated-training');

        $runs = MLflow::getRecordedRuns();
        $this->assertCount(1, $runs);

        $runId = $runs[0]['run_id'];
        MLflow::assertParamLogged($runId, 'model_type', 'classifier');
    }
}
```

### Testing with Builder Pattern

```php
public function test_uses_run_builder()
{
    MLflow::fake();

    $experiment = MLflow::experiments()->create('test-exp');

    $run = MLflow::createRunBuilder($experiment->experimentId)
        ->withName('test-run')
        ->withParam('lr', '0.01')
        ->withMetric('accuracy', 0.95)
        ->start();

    MLflow::assertRunStarted($experiment->experimentId);
    MLflow::assertParamLogged($run->info->runId, 'lr', '0.01');
    MLflow::assertMetricLogged($run->info->runId, 'accuracy', 0.95);
}
```

## Advanced Testing

### Inspecting Metric Values

```php
public function test_metric_values_increase()
{
    MLflow::fake();

    $experiment = MLflow::experiments()->create('test');
    $run = MLflow::runs()->create($experiment->experimentId);

    // Log multiple metrics
    MLflow::runs()->logMetric($run->info->runId, 'loss', 1.0, null, 0);
    MLflow::runs()->logMetric($run->info->runId, 'loss', 0.5, null, 1);
    MLflow::runs()->logMetric($run->info->runId, 'loss', 0.2, null, 2);

    // Get all recorded metrics
    $metrics = MLflow::getRecordedMetrics();

    $lossMetrics = array_filter($metrics, fn($m) => $m['key'] === 'loss');
    $this->assertCount(3, $lossMetrics);

    // Verify values are decreasing
    $values = array_column(array_values($lossMetrics), 'value');
    $this->assertEquals([1.0, 0.5, 0.2], $values);
}
```

### Testing Error Handling

```php
public function test_handles_mlflow_errors_gracefully()
{
    // Don't fake MLflow, use real client with invalid URI
    config(['mlflow.tracking_uri' => 'http://invalid-server:5000']);

    $service = new ModelTrainingService();

    // Should not throw, should log error instead
    $result = $service->trainWithFallback([]);

    $this->assertFalse($result['mlflow_logged']);
    $this->assertNotNull($result['error']);
}
```

### Resetting Between Tests

The fake automatically resets when you call `MLflow::fake()` again, but you can also manually reset:

```php
public function test_first_test()
{
    $fake = MLflow::fake();

    MLflow::experiments()->create('test-1');

    MLflow::assertExperimentCreated('test-1');
}

public function test_second_test()
{
    $fake = MLflow::fake(); // Automatically resets

    // Previous experiments are not present
    MLflow::assertNothingLogged();

    MLflow::experiments()->create('test-2');

    MLflow::assertExperimentCreated('test-2');
}
```

Or manually:

```php
$fake = MLflow::fake();

// Do some operations
MLflow::experiments()->create('test');

// Reset the fake
$fake->reset();

// Now it's clean
MLflow::assertNothingLogged();
```

## Tips and Best Practices

1. **Always fake MLflow in tests** - Never connect to a real MLflow server in unit tests
2. **Use setUp() method** - Initialize the fake in setUp() to avoid repetition
3. **Test one thing at a time** - Focus each test on a specific behavior
4. **Use specific assertions** - Prefer `assertMetricLogged` over manual array inspection
5. **Test error cases** - Don't just test the happy path
6. **Mock time-dependent tests** - Use Carbon for consistent timestamps

## Integration with Laravel Testing

### HTTP Tests

```php
public function test_api_endpoint_logs_metrics()
{
    MLflow::fake();

    $response = $this->postJson('/api/train', [
        'model' => 'classifier',
        'params' => ['lr' => 0.01],
    ]);

    $response->assertOk();

    MLflow::assertExperimentCreated('api-training');
}
```

### Database Transactions

The fake works seamlessly with database transactions:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class TrainingTest extends TestCase
{
    use RefreshDatabase;

    public function test_saves_to_database_and_mlflow()
    {
        MLflow::fake();

        $model = Model::create(['name' => 'test']);

        $this->service->train($model);

        $this->assertDatabaseHas('models', ['name' => 'test']);
        MLflow::assertExperimentCreated('model-training');
    }
}
```

## Troubleshooting

### Assertion Failures

If assertions fail, inspect the recorded data:

```php
$fake = MLflow::fake();

// Your code...

// Debug what was actually recorded
dd([
    'experiments' => $fake->getRecordedExperiments(),
    'runs' => $fake->getRecordedRuns(),
    'metrics' => $fake->getRecordedMetrics(),
    'params' => $fake->getRecordedParams(),
]);
```

### Fake Not Working

Make sure you're using the facade and not injecting the client directly:

```php
// Good
use MLflow\Laravel\Facades\MLflow;
MLflow::fake();

// Won't work - dependency injection bypasses facade
public function __construct(MLflowClient $client) {
    $this->client = $client;
}
```

To test with dependency injection, bind the fake in your test:

```php
$fake = MLflow\Testing\Fakes\MLflowFake::create();
$this->app->instance(MLflowClient::class, $fake);
```
