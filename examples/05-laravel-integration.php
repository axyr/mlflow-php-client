<?php

/**
 * Laravel Integration Example
 *
 * This example demonstrates Laravel-specific features:
 * - Using the MLflow facade
 * - Dependency injection
 * - Testing with fakes
 *
 * Note: This is pseudo-code showing Laravel integration patterns.
 * For a real Laravel app, use these patterns in your controllers/services.
 */

namespace App\Services;

use MLflow\Contracts\MLflowClientContract;
use MLflow\Contracts\RunApiContract;
use MLflow\Laravel\Facades\MLflow;

/**
 * Training Service using Dependency Injection
 */
class ModelTrainingService
{
    public function __construct(
        private MLflowClientContract $mlflow,
        private RunApiContract $runs
    ) {}

    public function train(array $config): string
    {
        // Create experiment
        $experiment = $this->mlflow->experiments()->create($config['experiment_name']);

        // Create run with builder
        $run = $this->mlflow->createRunBuilder($experiment->experimentId)
            ->withName($config['run_name'])
            ->withParam('learning_rate', $config['lr'])
            ->withParam('batch_size', $config['batch_size'])
            ->withTag('environment', config('app.env'))
            ->withTag('user', auth()->user()?->email ?? 'system')
            ->start();

        // Simulate training
        $metrics = $this->trainModel($config);

        // Log metrics
        foreach ($metrics as $step => $values) {
            $this->runs->logMetric($run->info->runId, 'accuracy', $values['accuracy'], step: $step);
            $this->runs->logMetric($run->info->runId, 'loss', $values['loss'], step: $step);
        }

        // Complete run
        $this->runs->setTerminated($run->info->runId, RunStatus::FINISHED);

        return $run->info->runId;
    }

    private function trainModel(array $config): array
    {
        // Your actual training logic here
        return [
            1 => ['accuracy' => 0.85, 'loss' => 0.45],
            2 => ['accuracy' => 0.90, 'loss' => 0.30],
            3 => ['accuracy' => 0.93, 'loss' => 0.22],
        ];
    }
}

/**
 * Using the Facade (Quick and Easy)
 */
class QuickExampleController
{
    public function store(Request $request)
    {
        // Direct facade usage
        $experiment = MLflow::experiments()->create('quick-experiment');

        $run = MLflow::createRunBuilder($experiment->experimentId)
            ->withParam('model', $request->model)
            ->withTag('source', 'api')
            ->start();

        return response()->json([
            'run_id' => $run->info->runId,
        ]);
    }
}

/**
 * Testing with Fakes
 */
class ModelTrainingServiceTest extends TestCase
{
    public function test_trains_model_successfully()
    {
        // Replace MLflow with a fake
        MLflow::fake();

        // Execute service
        $service = app(ModelTrainingService::class);
        $runId = $service->train([
            'experiment_name' => 'test-experiment',
            'run_name' => 'test-run',
            'lr' => '0.01',
            'batch_size' => '32',
        ]);

        // Assert MLflow interactions
        MLflow::assertExperimentCreated('test-experiment');
        MLflow::assertRunStarted('test-experiment');
        MLflow::assertMetricLogged($runId, 'accuracy');
        MLflow::assertParamLogged($runId, 'learning_rate', '0.01');
    }
}

/**
 * Listening to Events
 */
class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \MLflow\Laravel\Events\RunStarted::class => [
            \App\Listeners\NotifyTeamAboutRun::class,
        ],
        \MLflow\Laravel\Events\MetricLogged::class => [
            \App\Listeners\CheckMetricThresholds::class,
        ],
        \MLflow\Laravel\Events\ModelRegistered::class => [
            \App\Listeners\TriggerDeploymentPipeline::class,
        ],
    ];
}

/**
 * Artisan Commands
 */
class TrainModelCommand extends Command
{
    protected $signature = 'model:train {--experiment=default}';

    public function handle(ModelTrainingService $service)
    {
        $this->info('Starting model training...');

        $runId = $service->train([
            'experiment_name' => $this->option('experiment'),
            'run_name' => 'cli-training-' . now()->timestamp,
            'lr' => '0.001',
            'batch_size' => '64',
        ]);

        $this->info("✅ Training completed! Run ID: {$runId}");

        // List experiments
        $this->call('mlflow:experiments:list');
    }
}

/**
 * Job Queue Integration
 */
class TrainModelJob implements ShouldQueue
{
    public function __construct(
        private array $config
    ) {}

    public function handle(ModelTrainingService $service)
    {
        $runId = $service->train($this->config);

        Log::info('Model training completed', [
            'run_id' => $runId,
            'config' => $this->config,
        ]);
    }
}

/**
 * Helper Functions
 */
function trackExperiment(string $name): string
{
    $experiment = mlflow()->experiments()->create($name);

    return $experiment->experimentId;
}

function logTrainingMetrics(string $runId, array $metrics): void
{
    foreach ($metrics as $key => $value) {
        mlflow_log_metric($runId, $key, $value);
    }
}

echo "📖 This file demonstrates Laravel integration patterns.\n";
echo "   Copy these patterns into your Laravel application!\n";
