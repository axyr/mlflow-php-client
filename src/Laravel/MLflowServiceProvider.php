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
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/mlflow.php' => config_path('mlflow.php'),
            ], 'mlflow-config');
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
