<?php

declare(strict_types=1);

namespace MLflow\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use MLflow\Api\ArtifactApi;
use MLflow\Api\DatasetApi;
use MLflow\Api\ExperimentApi;
use MLflow\Api\MetricApi;
use MLflow\Api\ModelRegistryApi;
use MLflow\Api\PromptApi;
use MLflow\Api\RunApi;
use MLflow\Api\TraceApi;
use MLflow\Api\WebhookApi;
use MLflow\Builder\ExperimentBuilder;
use MLflow\Builder\ModelBuilder;
use MLflow\Builder\RunBuilder;
use MLflow\Builder\TraceBuilder;

/**
 * MLflow Facade
 *
 * @method static ExperimentApi experiments()
 * @method static RunApi runs()
 * @method static ModelRegistryApi modelRegistry()
 * @method static MetricApi metrics()
 * @method static ArtifactApi artifacts()
 * @method static TraceApi traces()
 * @method static PromptApi prompts()
 * @method static WebhookApi webhooks()
 * @method static DatasetApi datasets()
 * @method static RunBuilder createRunBuilder(string $experimentId)
 * @method static ExperimentBuilder createExperimentBuilder(string $name)
 * @method static ModelBuilder createModelBuilder(string $name)
 * @method static TraceBuilder createTraceBuilder(string $experimentId, string $name)
 * @method static bool validateConnection()
 * @method static array<string, mixed> getServerInfo()
 *
 * @see \MLflow\MLflowClient
 */
class MLflow extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'mlflow';
    }
}
