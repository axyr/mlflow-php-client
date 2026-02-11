# Experiments and Model Training

While PHP isn't typically used for model training, MLflow's experiment tracking can still be useful for tracking ML pipeline orchestration, model evaluation, and A/B testing scenarios.

## Basic Experiment Tracking

### Creating Experiments and Runs

```php
use MLflow\Laravel\Facades\MLflow;

// Create experiment
$experiment = MLflow::experiments()->create('model-evaluation');

// Create run with builder
$run = MLflow::createRunBuilder($experiment->experimentId)
    ->withName('evaluation-run-001')
    ->withParam('model_version', 'v2.1')
    ->withParam('dataset', 'test-set-2024')
    ->withMetric('accuracy', 0.95)
    ->withMetric('f1_score', 0.93)
    ->withTag('environment', 'production')
    ->start();

// Log additional metrics
MLflow::runs()->logMetric($run->info->runId, 'precision', 0.94, step: 1);
MLflow::runs()->logMetric($run->info->runId, 'recall', 0.92, step: 1);
```

### Dependency Injection

```php
use MLflow\Contracts\MLflowClientContract;
use MLflow\Contracts\RunApiContract;

class ModelEvaluationService
{
    public function __construct(
        private MLflowClientContract $mlflow,
        private RunApiContract $runs
    ) {}

    public function evaluate(string $modelVersion): void
    {
        $run = $this->runs->create($this->experimentId);

        // Evaluation logic...
        $metrics = $this->runEvaluation($modelVersion);

        foreach ($metrics as $key => $value) {
            $this->runs->logMetric($run->info->runId, $key, $value);
        }
    }
}
```

## Model Registry

Track model versions and lifecycle stages:

```php
// Register a new model
$model = MLflow::modelRegistry()->createRegisteredModel(
    name: 'recommendation-engine',
    description: 'Product recommendation model'
);

// Create model version
$version = MLflow::modelRegistry()->createModelVersion(
    name: 'recommendation-engine',
    source: 's3://models/recommendation-v1',
    runId: $runId
);

// Transition to production
MLflow::modelRegistry()->transitionModelVersionStage(
    name: 'recommendation-engine',
    version: '1',
    stage: 'Production'
);

// Search models
$models = MLflow::modelRegistry()->searchRegisteredModels(
    filter: "name LIKE '%recommendation%'"
);
```

## Batch Operations

```php
use MLflow\Request\LogBatch;

// Log multiple metrics and parameters at once
$batch = new LogBatch(
    metrics: [
        ['key' => 'accuracy', 'value' => 0.95, 'timestamp' => time() * 1000, 'step' => 0],
        ['key' => 'loss', 'value' => 0.05, 'timestamp' => time() * 1000, 'step' => 0],
    ],
    params: [
        ['key' => 'learning_rate', 'value' => '0.01'],
        ['key' => 'batch_size', 'value' => '32'],
    ],
    tags: [
        ['key' => 'framework', 'value' => 'pytorch'],
        ['key' => 'optimizer', 'value' => 'adam'],
    ]
);

MLflow::runs()->logBatch($runId, $batch);
```

## Searching Experiments and Runs

```php
// Search experiments
$experiments = MLflow::experiments()->search(
    filter: "name LIKE '%production%'",
    maxResults: 10
);

// Search runs with filters
$result = MLflow::runs()->search(
    experimentIds: [$experimentId],
    filter: "metrics.accuracy > 0.9 AND params.model_type = 'transformer'",
    orderBy: ['metrics.accuracy DESC'],
    maxResults: 5
);

foreach ($result['runs'] as $run) {
    $metrics = $run->data->metrics->getLatestByKey();
    echo "Run: {$run->info->runId}\n";
    echo "Accuracy: {$metrics['accuracy']->value}\n";
}
```

## Artifacts

Upload and download model artifacts:

```php
// Upload artifact
MLflow::artifacts()->uploadArtifact(
    runId: $runId,
    localPath: '/path/to/model.pkl',
    artifactPath: 'models/'
);

// List artifacts
$artifacts = MLflow::artifacts()->listArtifacts($runId);

// Download artifact
$content = MLflow::artifacts()->downloadArtifact($runId, 'models/model.pkl');
file_put_contents('/local/path/model.pkl', $content);
```

## Custom Macros

Extend builders with reusable patterns:

```php
use MLflow\Builder\RunBuilder;

// Define macro in AppServiceProvider
RunBuilder::macro('withProductionDefaults', function() {
    return $this
        ->withTag('environment', 'production')
        ->withTag('deployed_by', auth()->user()?->email ?? 'system')
        ->withTag('server', gethostname());
});

// Use macro
$run = MLflow::createRunBuilder($experimentId)
    ->withProductionDefaults()
    ->withMetric('accuracy', 0.95)
    ->start();
```

## Events

React to experiment tracking events:

```php
use MLflow\Laravel\Events\{RunStarted, MetricLogged, ModelRegistered};

Event::listen(RunStarted::class, function ($event) {
    Log::info('Evaluation run started', [
        'run_id' => $event->run->info->runId
    ]);
});

Event::listen(MetricLogged::class, function ($event) {
    // Send metrics to monitoring system
    Metrics::gauge('mlflow.metric', $event->value, [
        'key' => $event->key,
    ]);
});

Event::listen(ModelRegistered::class, function ($event) {
    // Notify team when new model is registered
    Notification::route('slack', config('slack.webhook'))
        ->notify(new ModelRegisteredNotification($event->model));
});
```

## Complete API Reference

### Experiments
```php
MLflow::experiments()->create('name')
MLflow::experiments()->getById('exp-123')
MLflow::experiments()->search($filter)
MLflow::experiments()->update($id, $newName)
MLflow::experiments()->delete($id)
```

### Runs
```php
MLflow::runs()->create($experimentId)
MLflow::runs()->getById($runId)
MLflow::runs()->update($runId, $status, $endTime)
MLflow::runs()->delete($runId)
MLflow::runs()->logMetric($runId, 'key', 0.95)
MLflow::runs()->logParameter($runId, 'key', 'value')
MLflow::runs()->logBatch($runId, $batch)
MLflow::runs()->search($experimentIds, $filter)
```

### Model Registry
```php
MLflow::modelRegistry()->createRegisteredModel('name')
MLflow::modelRegistry()->getRegisteredModel('name')
MLflow::modelRegistry()->searchRegisteredModels($filter)
MLflow::modelRegistry()->createModelVersion('name', 's3://path')
MLflow::modelRegistry()->getModelVersion('name', '1')
MLflow::modelRegistry()->transitionModelVersionStage('name', '1', 'Production')
```

### Artifacts
```php
MLflow::artifacts()->listArtifacts($runId)
MLflow::artifacts()->downloadArtifact($runId, 'path')
MLflow::artifacts()->uploadArtifact($runId, $localPath, $artifactPath)
```

## See Also

- [Testing Guide](TESTING.md) - Test experiment tracking code
- [Integration Patterns](INTEGRATION_PATTERNS.md) - Real-world examples
- [Best Practices](BEST_PRACTICES.md) - Performance tips
