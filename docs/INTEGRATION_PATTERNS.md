# Integration Patterns

Common patterns for integrating MLflow into Laravel applications.

## Table of Contents

- [Service Layer Integration](#service-layer-integration)
- [Job Queue Integration](#job-queue-integration)
- [Event-Driven Architecture](#event-driven-architecture)
- [Middleware Integration](#middleware-integration)
- [Command Integration](#command-integration)
- [Real-World Examples](#real-world-examples)

## Service Layer Integration

### Basic Training Service

```php
namespace App\Services;

use MLflow\Laravel\Facades\MLflow;

class ModelTrainingService
{
    public function train(array $params): array
    {
        // Create or get experiment
        $experiment = $this->getOrCreateExperiment('model-training');

        // Start a run with builder pattern
        $run = MLflow::createRunBuilder($experiment->experimentId)
            ->withName('training-' . time())
            ->withParams($params)
            ->withTag('environment', app()->environment())
            ->withTag('user', auth()->id())
            ->start();

        try {
            // Your training logic
            $result = $this->performTraining($params);

            // Log metrics
            foreach ($result['metrics'] as $key => $value) {
                MLflow::runs()->logMetric(
                    $run->info->runId,
                    $key,
                    $value
                );
            }

            // Mark as successful
            MLflow::runs()->setTerminated(
                $run->info->runId,
                \MLflow\Enum\RunStatus::FINISHED
            );

            return $result;
        } catch (\Exception $e) {
            // Mark as failed
            MLflow::runs()->setTerminated(
                $run->info->runId,
                \MLflow\Enum\RunStatus::FAILED
            );

            throw $e;
        }
    }

    private function getOrCreateExperiment(string $name)
    {
        try {
            return MLflow::experiments()->getByName($name);
        } catch (\MLflow\Exception\NotFoundException $e) {
            return MLflow::experiments()->create($name);
        }
    }
}
```

### Batch Processing Service

```php
namespace App\Services;

use MLflow\Laravel\Facades\MLflow;

class BatchProcessingService
{
    public function processBatch(array $items): void
    {
        $experiment = $this->getOrCreateExperiment('batch-processing');

        $run = MLflow::runs()->create($experiment->experimentId);
        $runId = $run->info->runId;

        $processed = 0;
        $failed = 0;

        foreach ($items as $index => $item) {
            try {
                $this->processItem($item);
                $processed++;

                // Log progress every 10 items
                if ($index % 10 === 0) {
                    MLflow::runs()->logMetric($runId, 'processed', $processed, null, $index);
                    MLflow::runs()->logMetric($runId, 'failed', $failed, null, $index);
                }
            } catch (\Exception $e) {
                $failed++;
            }
        }

        // Log final results
        MLflow::runs()->logBatch(
            $runId,
            metrics: [
                ['key' => 'total_processed', 'value' => $processed],
                ['key' => 'total_failed', 'value' => $failed],
                ['key' => 'success_rate', 'value' => $processed / count($items)],
            ],
            params: [
                'total_items' => (string) count($items),
                'batch_id' => (string) time(),
            ]
        );

        MLflow::runs()->setTerminated($runId);
    }
}
```

## Job Queue Integration

### Tracked Job

```php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use MLflow\Laravel\Facades\MLflow;

class TrainModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $experimentId;
    private string $runId;

    public function __construct(
        public array $params,
        public string $modelType
    ) {}

    public function handle(): void
    {
        // Create experiment and run
        $experiment = MLflow::experiments()->create(
            'job-training-' . $this->modelType
        );

        $run = MLflow::createRunBuilder($experiment->experimentId)
            ->withName("job-{$this->job->getJobId()}")
            ->withParams($this->params)
            ->withTag('job_id', (string) $this->job->getJobId())
            ->withTag('queue', $this->queue)
            ->start();

        $this->experimentId = $experiment->experimentId;
        $this->runId = $run->info->runId;

        try {
            $result = $this->train();

            // Log results
            MLflow::runs()->logBatch(
                $this->runId,
                metrics: $result['metrics'],
                params: $result['params']
            );

            MLflow::runs()->setTerminated($this->runId);
        } catch (\Exception $e) {
            MLflow::runs()->setTag($this->runId, 'error', $e->getMessage());
            MLflow::runs()->setTerminated(
                $this->runId,
                \MLflow\Enum\RunStatus::FAILED
            );

            throw $e;
        }
    }

    private function train(): array
    {
        // Training logic
        return [
            'metrics' => [
                ['key' => 'accuracy', 'value' => 0.95],
            ],
            'params' => [
                'trained_at' => now()->toIso8601String(),
            ],
        ];
    }

    public function failed(\Throwable $exception): void
    {
        if (isset($this->runId)) {
            MLflow::runs()->setTag($this->runId, 'failed_reason', $exception->getMessage());
            MLflow::runs()->setTerminated(
                $this->runId,
                \MLflow\Enum\RunStatus::FAILED
            );
        }
    }
}
```

## Event-Driven Architecture

### Event Listener

```php
namespace App\Listeners;

use App\Events\ModelTrainingCompleted;
use MLflow\Laravel\Facades\MLflow;

class LogTrainingMetrics
{
    public function handle(ModelTrainingCompleted $event): void
    {
        $experiment = MLflow::experiments()->getByName('model-deployments');

        $run = MLflow::createRunBuilder($experiment->experimentId)
            ->withName($event->model->name)
            ->withParam('model_id', (string) $event->model->id)
            ->withParam('version', $event->version)
            ->withMetric('accuracy', $event->accuracy)
            ->withMetric('training_time', $event->trainingTime)
            ->withTag('deployed', 'true')
            ->start();

        // Store run ID for future reference
        $event->model->update(['mlflow_run_id' => $run->info->runId]);
    }
}
```

### Custom Event

```php
namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ModelTrainingCompleted
{
    use Dispatchable;

    public function __construct(
        public $model,
        public float $accuracy,
        public int $trainingTime,
        public string $version
    ) {}
}
```

Register in `EventServiceProvider`:

```php
protected $listen = [
    ModelTrainingCompleted::class => [
        LogTrainingMetrics::class,
    ],
];
```

## Middleware Integration

### Request Tracking Middleware

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use MLflow\Laravel\Facades\MLflow;

class TrackApiRequests
{
    public function handle(Request $request, Closure $next)
    {
        if (!$this->shouldTrack($request)) {
            return $next($request);
        }

        $experiment = $this->getExperiment();
        $run = MLflow::runs()->create($experiment->experimentId);
        $runId = $run->info->runId;

        $request->attributes->set('mlflow_run_id', $runId);

        // Log request details
        MLflow::runs()->logBatch(
            $runId,
            params: [
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'user_id' => (string) ($request->user()?->id ?? 'guest'),
            ]
        );

        $startTime = microtime(true);

        $response = $next($request);

        // Log response details
        MLflow::runs()->logBatch(
            $runId,
            metrics: [
                ['key' => 'response_time', 'value' => microtime(true) - $startTime],
                ['key' => 'status_code', 'value' => $response->getStatusCode()],
            ],
            tags: [
                'success' => $response->isSuccessful() ? 'true' : 'false',
            ]
        );

        MLflow::runs()->setTerminated($runId);

        return $response;
    }

    private function shouldTrack(Request $request): bool
    {
        return $request->is('api/ml/*');
    }

    private function getExperiment()
    {
        try {
            return MLflow::experiments()->getByName('api-requests');
        } catch (\MLflow\Exception\NotFoundException $e) {
            return MLflow::experiments()->create('api-requests');
        }
    }
}
```

## Command Integration

### Artisan Command with MLflow

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use MLflow\Laravel\Facades\MLflow;

class TrainModelsCommand extends Command
{
    protected $signature = 'models:train {--type=} {--epochs=10}';
    protected $description = 'Train ML models with MLflow tracking';

    public function handle(): int
    {
        $this->info('Starting model training...');

        $experiment = MLflow::createExperimentBuilder('cli-training')
            ->withTag('source', 'cli')
            ->withTag('command', $this->signature)
            ->create();

        $run = MLflow::createRunBuilder($experiment->experimentId)
            ->withName('cli-' . now()->format('Y-m-d-H-i-s'))
            ->withParam('model_type', $this->option('type') ?? 'default')
            ->withParam('epochs', $this->option('epochs'))
            ->withTag('user', get_current_user())
            ->start();

        $progressBar = $this->output->createProgressBar((int) $this->option('epochs'));

        for ($epoch = 0; $epoch < $this->option('epochs'); $epoch++) {
            // Training logic here
            $loss = $this->trainEpoch($epoch);

            MLflow::runs()->logMetric(
                $run->info->runId,
                'loss',
                $loss,
                null,
                $epoch
            );

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        MLflow::runs()->setTerminated($run->info->runId);

        $this->info("Training completed! Run ID: {$run->info->runId}");

        return Command::SUCCESS;
    }

    private function trainEpoch(int $epoch): float
    {
        // Your training logic
        return rand(10, 100) / 100;
    }
}
```

## Real-World Examples

### A/B Testing Service

```php
namespace App\Services;

use MLflow\Laravel\Facades\MLflow;

class ABTestingService
{
    public function runExperiment(string $experimentName, array $variants): array
    {
        $experiment = MLflow::createExperimentBuilder($experimentName)
            ->withTag('type', 'ab_test')
            ->withTag('variants', implode(',', array_keys($variants)))
            ->create();

        $results = [];

        foreach ($variants as $variantName => $variantConfig) {
            $run = MLflow::createRunBuilder($experiment->experimentId)
                ->withName("variant-{$variantName}")
                ->withParams($variantConfig)
                ->withTag('variant', $variantName)
                ->start();

            // Run variant
            $metrics = $this->runVariant($variantConfig);

            // Log results
            MLflow::runs()->logBatch(
                $run->info->runId,
                metrics: $metrics
            );

            MLflow::runs()->setTerminated($run->info->runId);

            $results[$variantName] = $metrics;
        }

        return $results;
    }

    private function runVariant(array $config): array
    {
        // Variant logic
        return [
            ['key' => 'conversion_rate', 'value' => 0.15],
            ['key' => 'avg_session_time', 'value' => 120.5],
        ];
    }
}
```

### Model Deployment Tracker

```php
namespace App\Services;

use MLflow\Laravel\Facades\MLflow;

class ModelDeploymentService
{
    public function deployModel(string $modelName, string $version, string $environment): void
    {
        $experiment = MLflow::experiments()->getByName('model-deployments');

        $run = MLflow::createRunBuilder($experiment->experimentId)
            ->withName("{$modelName}-{$version}-{$environment}")
            ->withParam('model_name', $modelName)
            ->withParam('version', $version)
            ->withParam('environment', $environment)
            ->withParam('deployed_at', now()->toIso8601String())
            ->withTag('status', 'deploying')
            ->start();

        try {
            $this->performDeployment($modelName, $version, $environment);

            MLflow::runs()->setTag($run->info->runId, 'status', 'deployed');
            MLflow::runs()->setTag($run->info->runId, 'deployment_url', $this->getDeploymentUrl());

            MLflow::runs()->setTerminated($run->info->runId);
        } catch (\Exception $e) {
            MLflow::runs()->setTag($run->info->runId, 'status', 'failed');
            MLflow::runs()->setTag($run->info->runId, 'error', $e->getMessage());
            MLflow::runs()->setTerminated($run->info->runId, \MLflow\Enum\RunStatus::FAILED);

            throw $e;
        }
    }

    private function performDeployment(string $modelName, string $version, string $environment): void
    {
        // Deployment logic
    }

    private function getDeploymentUrl(): string
    {
        return 'https://models.example.com/v1/predict';
    }
}
```

## Tips and Best Practices

1. **Use experiments to group related runs** - Create separate experiments for different projects or model types
2. **Tag everything** - Tags make it easy to filter and search runs later
3. **Log incrementally** - Don't wait until the end to log metrics, log as you go
4. **Use builders for cleaner code** - The fluent builder pattern makes your code more readable
5. **Handle failures gracefully** - Always mark failed runs appropriately
6. **Store run IDs** - Keep track of MLflow run IDs in your database for future reference
7. **Use batch logging** - When logging multiple metrics/params, use `logBatch` for efficiency
8. **Leverage contracts** - Use dependency injection with contracts for better testability
