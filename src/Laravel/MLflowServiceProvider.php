<?php

declare(strict_types=1);

namespace MLflow\Laravel;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use MLflow\MLflowClient;

class MLflowServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/mlflow.php',
            'mlflow'
        );

        $this->app->singleton(MLflowClient::class, function ($app) {
            $config = $app['config']['mlflow'];

            return new MLflowClient(
                trackingUri: $config['tracking_uri'],
                config: $config['options'],
                logger: $config['logging']['enabled'] ? $app['log']->channel($config['logging']['channel']) : null
            );
        });

        $this->app->alias(MLflowClient::class, 'mlflow');

        // Bind contracts to implementations
        $this->app->bind(
            \MLflow\Contracts\MLflowClientContract::class,
            MLflowClient::class
        );

        $this->app->bind(
            \MLflow\Contracts\ExperimentApiContract::class,
            \MLflow\Api\ExperimentApi::class
        );

        $this->app->bind(
            \MLflow\Contracts\RunApiContract::class,
            \MLflow\Api\RunApi::class
        );

        $this->app->bind(
            \MLflow\Contracts\ModelRegistryApiContract::class,
            \MLflow\Api\ModelRegistryApi::class
        );

        $this->app->bind(
            \MLflow\Contracts\MetricApiContract::class,
            \MLflow\Api\MetricApi::class
        );

        $this->app->bind(
            \MLflow\Contracts\ArtifactApiContract::class,
            \MLflow\Api\ArtifactApi::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load helper functions
        require_once __DIR__ . '/helpers.php';

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/mlflow.php' => config_path('mlflow.php'),
            ], 'mlflow-config');

            $this->commands([
                Console\InstallCommand::class,
                Console\TestConnectionCommand::class,
                Console\ListExperimentsCommand::class,
                Console\ClearCacheCommand::class,
                Console\GenerateIdeHelperCommand::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [MLflowClient::class, 'mlflow'];
    }
}
