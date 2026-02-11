# Best Practices

Guidelines for using MLflow effectively in Laravel applications.

## Configuration

### Environment-Based Configuration

```php
// config/mlflow.php
return [
    'tracking_uri' => env('MLFLOW_TRACKING_URI', 'http://localhost:5000'),

    'options' => [
        'timeout' => env('MLFLOW_TIMEOUT', 30),
        'verify' => env('MLFLOW_VERIFY_SSL', true),
    ],

    'logging' => [
        'enabled' => env('MLFLOW_LOGGING_ENABLED', true),
        'channel' => env('MLFLOW_LOG_CHANNEL', 'stack'),
    ],
];
```

### Per-Environment Settings

```env
# .env.local
MLFLOW_TRACKING_URI=http://localhost:5000
MLFLOW_LOGGING_ENABLED=true

# .env.staging
MLFLOW_TRACKING_URI=https://mlflow-staging.example.com
MLFLOW_TIMEOUT=60

# .env.production
MLFLOW_TRACKING_URI=https://mlflow.example.com
MLFLOW_VERIFY_SSL=true
MLFLOW_TIMEOUT=30
```

## Naming Conventions

### Experiments

```php
// Good: Descriptive, environment-aware
$experiment = "production-recommendation-model";
$experiment = "staging-fraud-detection";
$experiment = "{$environment}-{$project}-{$model}";

// Bad: Vague, hard to search
$experiment = "test";
$experiment = "exp1";
```

### Runs

```php
// Good: Include timestamp and context
$runName = "training-" . now()->format('Y-m-d-H-i-s');
$runName = "batch-{$batchId}-{$timestamp}";
$runName = "{$modelType}-{$version}-{$timestamp}";

// Bad: No context
$runName = "run1";
$runName = "test";
```

## Tagging Strategy

### Standardized Tags

```php
MLflow::createRunBuilder($experimentId)
    // Environment info
    ->withTag('environment', app()->environment())
    ->withTag('server', gethostname())

    // User/source info
    ->withTag('user', auth()->user()->email ?? 'system')
    ->withTag('source', 'api') // or 'cli', 'job', 'web'

    // Version control
    ->withTag('git_commit', exec('git rev-parse HEAD'))
    ->withTag('git_branch', exec('git branch --show-current'))

    // Application info
    ->withTag('app_version', config('app.version'))
    ->withTag('php_version', PHP_VERSION)

    ->start();
```

### Searchable Tags

```php
// Make important fields searchable via tags
->withTag('model_type', 'classifier')
->withTag('dataset', 'v2023-10')
->withTag('framework', 'scikit-learn')
->withTag('status', 'production')

// Later, search by these tags
$runs = MLflow::runs()->search(
    experimentIds: [$experimentId],
    filter: "tags.model_type = 'classifier' AND tags.status = 'production'"
);
```

## Error Handling

### Graceful Degradation

```php
namespace App\Services;

use MLflow\Laravel\Facades\MLflow;
use Illuminate\Support\Facades\Log;

class ResilientTrainingService
{
    public function train(array $params): array
    {
        $runId = null;

        try {
            $experiment = MLflow::experiments()->create('training');
            $run = MLflow::runs()->create($experiment->experimentId);
            $runId = $run->info->runId;
        } catch (\Exception $e) {
            // Log error but continue training
            Log::warning('MLflow tracking failed', [
                'error' => $e->getMessage(),
            ]);
        }

        // Perform training regardless of MLflow status
        $result = $this->performTraining($params);

        // Try to log results
        if ($runId) {
            try {
                MLflow::runs()->logBatch($runId, metrics: $result['metrics']);
                MLflow::runs()->setTerminated($runId);
            } catch (\Exception $e) {
                Log::warning('MLflow logging failed', ['error' => $e->getMessage()]);
            }
        }

        return $result;
    }

    private function performTraining(array $params): array
    {
        // Training logic that should run regardless of MLflow
        return ['metrics' => [['key' => 'accuracy', 'value' => 0.95]]];
    }
}
```

### Retry Logic

```php
use Illuminate\Support\Facades\Retry;

public function logWithRetry(string $runId, array $metrics): void
{
    Retry::times(3)
        ->sleep(1000)
        ->whenInstanceOf(\MLflow\Exception\NetworkException::class)
        ->throw(fn() => MLflow::runs()->logBatch($runId, metrics: $metrics));
}
```

## Performance Optimization

### Batch Operations

```php
// Bad: Multiple API calls
foreach ($metrics as $metric) {
    MLflow::runs()->logMetric($runId, $metric['key'], $metric['value']);
}

// Good: Single API call
MLflow::runs()->logBatch(
    $runId,
    metrics: $metrics,
    params: $params,
    tags: $tags
);
```

### Lazy Initialization

```php
class TrainingService
{
    private ?string $runId = null;

    private function ensureRun(): string
    {
        if ($this->runId === null) {
            $experiment = MLflow::experiments()->getByName('training');
            $run = MLflow::runs()->create($experiment->experimentId);
            $this->runId = $run->info->runId;
        }

        return $this->runId;
    }

    public function logMetric(string $key, float $value): void
    {
        MLflow::runs()->logMetric($this->ensureRun(), $key, $value);
    }
}
```

### Async Logging with Jobs

```php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use MLflow\Laravel\Facades\MLflow;

class LogMLflowMetrics implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $runId,
        public array $metrics,
        public array $params = []
    ) {}

    public function handle(): void
    {
        MLflow::runs()->logBatch(
            $this->runId,
            metrics: $this->metrics,
            params: $this->params
        );
    }
}

// Usage
LogMLflowMetrics::dispatch($runId, $metrics, $params);
```

## Security

### Sensitive Data

```php
// Bad: Logging sensitive data
->withParam('api_key', $apiKey)
->withParam('password', $password)

// Good: Never log secrets
->withParam('api_key_length', strlen($apiKey))
->withParam('auth_method', 'api_key')

// Good: Hash sensitive values if needed
->withParam('user_hash', hash('sha256', $userId))
```

### Network Security

```php
// config/mlflow.php
'options' => [
    'verify' => env('MLFLOW_VERIFY_SSL', true), // Always verify in production
    'timeout' => 30,
    'headers' => [
        'Authorization' => 'Bearer ' . env('MLFLOW_API_TOKEN'),
    ],
],
```

## Testing

### Always Use Fakes in Tests

```php
// Good
public function test_trains_model()
{
    MLflow::fake();

    $this->service->train();

    MLflow::assertExperimentCreated('training');
}

// Bad: Connects to real server
public function test_trains_model()
{
    $this->service->train(); // Might fail if server is down
}
```

### Test MLflow Integration Separately

```php
// Feature test
public function test_training_service()
{
    MLflow::fake();

    $result = $this->service->train(['lr' => 0.01]);

    $this->assertTrue($result['success']);
    MLflow::assertMetricLogged($this->getRunId(), 'accuracy');
}

// Integration test (optional, separate test suite)
/**
 * @group integration
 * @group mlflow
 */
public function test_mlflow_connection()
{
    // Only runs when explicitly requested
    $client = app(MLflowClient::class);
    $this->assertTrue($client->validateConnection());
}
```

## Monitoring

### Health Checks

```php
namespace App\Http\Controllers;

use MLflow\Laravel\Facades\MLflow;

class HealthController extends Controller
{
    public function mlflow()
    {
        try {
            $info = app(MLflowClient::class)->getServerInfo();

            return response()->json([
                'status' => $info['reachable'] ? 'healthy' : 'degraded',
                'mlflow' => $info,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
```

### Logging

```php
// Enable MLflow client logging
'logging' => [
    'enabled' => true,
    'channel' => 'mlflow', // Create dedicated channel
],

// config/logging.php
'channels' => [
    'mlflow' => [
        'driver' => 'daily',
        'path' => storage_path('logs/mlflow.log'),
        'level' => 'info',
        'days' => 14,
    ],
],
```

## Data Management

### Cleanup Old Runs

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use MLflow\Laravel\Facades\MLflow;

class CleanupOldRuns extends Command
{
    protected $signature = 'mlflow:cleanup {--days=30}';

    public function handle(): int
    {
        $cutoff = now()->subDays($this->option('days'));

        $experiments = MLflow::experiments()->search();

        foreach ($experiments['experiments'] as $experiment) {
            $runs = MLflow::runs()->search(
                experimentIds: [$experiment->experimentId],
                filter: "attributes.start_time < {$cutoff->timestamp}000"
            );

            foreach ($runs['runs'] as $run) {
                MLflow::runs()->deleteRun($run->info->runId);
                $this->info("Deleted run: {$run->info->runId}");
            }
        }

        return Command::SUCCESS;
    }
}
```

## Documentation

### Code Documentation

```php
/**
 * Train a model with MLflow tracking
 *
 * This method creates an MLflow experiment if it doesn't exist,
 * starts a new run, logs all parameters and metrics during training,
 * and properly closes the run when complete.
 *
 * @param array $params Training parameters
 * @return array Training results with MLflow run ID
 *
 * @throws \MLflow\Exception\MLflowException If tracking fails
 *
 * @example
 * ```php
 * $result = $service->train([
 *     'learning_rate' => 0.01,
 *     'epochs' => 100,
 * ]);
 * // $result['mlflow_run_id'] contains the run ID
 * ```
 */
public function train(array $params): array
{
    // Implementation
}
```

### README for MLflow Setup

Create a `docs/MLFLOW_SETUP.md` in your project:

```markdown
# MLflow Setup

## Prerequisites

- Docker (recommended) or Python 3.8+
- Access to artifact store (S3, local filesystem, etc.)

## Local Development

# docker-compose.yml
mlflow:
  image: ghcr.io/mlflow/mlflow:latest
  ports:
    - "5000:5000"
  command: mlflow server --host 0.0.0.0

# .env
MLFLOW_TRACKING_URI=http://localhost:5000
```

## Summary

1. **Configure properly** - Use environment variables for flexibility
2. **Name consistently** - Follow naming conventions for easy searching
3. **Tag extensively** - Tags make filtering and analysis easier
4. **Handle errors** - Don't let MLflow failures stop your application
5. **Optimize performance** - Use batch operations and async logging
6. **Secure your data** - Never log secrets or sensitive information
7. **Test thoroughly** - Always use fakes in tests
8. **Monitor health** - Implement health checks and logging
9. **Clean up regularly** - Don't let old data accumulate
10. **Document well** - Help your team understand the implementation
