<?php

declare(strict_types=1);

namespace MLflow\Testing\Fakes;

use MLflow\Builder\ExperimentBuilder;
use MLflow\Builder\ModelBuilder;
use MLflow\Builder\RunBuilder;
use MLflow\Builder\TraceBuilder;
use MLflow\Testing\Concerns\AssertionsHelper;

/**
 * Fake MLflow client for testing purposes
 *
 * This fake implementation allows you to test your MLflow integration
 * without actually connecting to an MLflow server.
 *
 * @example
 * ```php
 * use MLflow\Laravel\Facades\MLflow;
 *
 * // In your test
 * MLflow::fake();
 *
 * // Your code that uses MLflow
 * $experiment = MLflow::experiments()->create('test-experiment');
 * $run = MLflow::runs()->create($experiment->experimentId);
 * MLflow::runs()->logMetric($run->info->runId, 'accuracy', 0.95);
 *
 * // Assert interactions
 * MLflow::assertExperimentCreated('test-experiment');
 * MLflow::assertRunStarted($experiment->experimentId);
 * MLflow::assertMetricLogged($run->info->runId, 'accuracy', 0.95);
 * ```
 */
class MLflowFake
{
    use AssertionsHelper;

    /** @var array<int, array<string, mixed>> */
    protected array $recordedExperiments = [];

    /** @var array<int, array<string, mixed>> */
    protected array $recordedRuns = [];

    /** @var array<int, array<string, mixed>> */
    protected array $recordedMetrics = [];

    /** @var array<int, array<string, mixed>> */
    protected array $recordedParams = [];

    /** @var array<int, array<string, mixed>> */
    protected array $recordedTags = [];

    /** @var array<int, array<string, mixed>> */
    protected array $recordedModels = [];

    private ?FakeExperimentApi $fakeExperimentApi = null;

    private ?FakeRunApi $fakeRunApi = null;

    private ?FakeModelRegistryApi $fakeModelRegistryApi = null;

    /**
     * Create a new MLflowFake instance
     */
    public function __construct()
    {
        // No initialization needed for fake
    }

    /**
     * Create a new fake instance
     */
    public static function create(): self
    {
        return new self;
    }

    /**
     * Get the fake Experiment API instance
     */
    public function experiments(): FakeExperimentApi
    {
        if ($this->fakeExperimentApi === null) {
            $this->fakeExperimentApi = new FakeExperimentApi($this);
        }

        return $this->fakeExperimentApi;
    }

    /**
     * Get the fake Run API instance
     */
    public function runs(): FakeRunApi
    {
        if ($this->fakeRunApi === null) {
            $this->fakeRunApi = new FakeRunApi($this);
        }

        return $this->fakeRunApi;
    }

    /**
     * Get the fake Model Registry API instance
     */
    public function modelRegistry(): FakeModelRegistryApi
    {
        if ($this->fakeModelRegistryApi === null) {
            $this->fakeModelRegistryApi = new FakeModelRegistryApi($this);
        }

        return $this->fakeModelRegistryApi;
    }

    /**
     * Get the fake Model Registry API instance (alias)
     */
    public function models(): FakeModelRegistryApi
    {
        return $this->modelRegistry();
    }

    /**
     * Record an experiment creation
     *
     * @param array<string, mixed> $data
     */
    public function recordExperiment(array $data): void
    {
        $this->recordedExperiments[] = $data;
    }

    /**
     * Record a run creation
     *
     * @param array<string, mixed> $data
     */
    public function recordRun(array $data): void
    {
        $this->recordedRuns[] = $data;
    }

    /**
     * Record a metric log
     */
    public function recordMetric(string $runId, string $key, float $value, ?int $step = null): void
    {
        $this->recordedMetrics[] = [
            'run_id' => $runId,
            'key' => $key,
            'value' => $value,
            'step' => $step,
            'timestamp' => time(),
        ];
    }

    /**
     * Record a parameter log
     */
    public function recordParam(string $runId, string $key, string $value): void
    {
        $this->recordedParams[] = [
            'run_id' => $runId,
            'key' => $key,
            'value' => $value,
        ];
    }

    /**
     * Record a tag set
     */
    public function recordTag(string $runId, string $key, string $value): void
    {
        $this->recordedTags[] = [
            'run_id' => $runId,
            'key' => $key,
            'value' => $value,
        ];
    }

    /**
     * Record a model registration
     *
     * @param array<string, mixed> $data
     */
    public function recordModel(array $data): void
    {
        $this->recordedModels[] = $data;
    }

    /**
     * Get all recorded experiments
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRecordedExperiments(): array
    {
        return $this->recordedExperiments;
    }

    /**
     * Get all recorded runs
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRecordedRuns(): array
    {
        return $this->recordedRuns;
    }

    /**
     * Get all recorded metrics
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRecordedMetrics(): array
    {
        return $this->recordedMetrics;
    }

    /**
     * Get all recorded params
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRecordedParams(): array
    {
        return $this->recordedParams;
    }

    /**
     * Get all recorded tags
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRecordedTags(): array
    {
        return $this->recordedTags;
    }

    /**
     * Get all recorded models
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRecordedModels(): array
    {
        return $this->recordedModels;
    }

    /**
     * Reset all recorded data
     */
    public function reset(): void
    {
        $this->recordedExperiments = [];
        $this->recordedRuns = [];
        $this->recordedMetrics = [];
        $this->recordedParams = [];
        $this->recordedTags = [];
        $this->recordedModels = [];
    }

    /**
     * Create a run builder
     *
     * @phpstan-ignore argument.type
     */
    public function createRunBuilder(string $experimentId): RunBuilder
    {
        /** @phpstan-ignore argument.type */
        return new RunBuilder($this->runs(), $experimentId);
    }

    /**
     * Create an experiment builder
     *
     * @phpstan-ignore argument.type
     */
    public function createExperimentBuilder(string $name): ExperimentBuilder
    {
        /** @phpstan-ignore argument.type */
        return new ExperimentBuilder($this->experiments(), $name);
    }

    /**
     * Create a model builder
     *
     * @phpstan-ignore argument.type
     */
    public function createModelBuilder(string $name): ModelBuilder
    {
        /** @phpstan-ignore argument.type */
        return new ModelBuilder($this->modelRegistry(), $name);
    }

    /**
     * Create a trace builder
     */
    public function createTraceBuilder(string $experimentId, string $name): TraceBuilder
    {
        return new TraceBuilder($experimentId, $name);
    }

    /**
     * Validate connection (always returns true for fake)
     */
    public function validateConnection(): bool
    {
        return true;
    }

    /**
     * Get server info (fake implementation)
     *
     * @return array{version: string|null, reachable: bool, error: string|null}
     */
    public function getServerInfo(): array
    {
        return [
            'version' => 'fake',
            'reachable' => true,
            'error' => null,
        ];
    }

    /**
     * Get tracking URI (fake implementation)
     */
    public function getTrackingUri(): string
    {
        return 'http://fake-mlflow:5000';
    }
}
