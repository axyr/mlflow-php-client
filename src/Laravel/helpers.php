<?php

declare(strict_types=1);

use MLflow\MLflowClient;

if (! function_exists('mlflow')) {
    /**
     * Get the MLflow client instance
     */
    function mlflow(): MLflowClient
    {
        return app(MLflowClient::class);
    }
}

if (! function_exists('mlflow_experiment')) {
    /**
     * Quick access to experiment operations
     *
     * @return \MLflow\Model\Experiment|null
     *
     * @throws \MLflow\Exception\MLflowException
     */
    function mlflow_experiment(?string $experimentId = null)
    {
        if ($experimentId === null) {
            $experimentId = config('mlflow.default_experiment');
            if (is_string($experimentId)) {
                /** @var string $experimentId */
            } else {
                $experimentId = null;
            }
        }

        if ($experimentId === null) {
            throw new InvalidArgumentException('No experiment ID provided and no default configured');
        }

        return mlflow()->experiments()->getById($experimentId);
    }
}

if (! function_exists('mlflow_run')) {
    /**
     * Quick access to run operations
     *
     * @return \MLflow\Model\Run
     *
     * @throws \MLflow\Exception\MLflowException
     */
    function mlflow_run(string $runId)
    {
        return mlflow()->runs()->getById($runId);
    }
}

if (! function_exists('mlflow_log_metric')) {
    /**
     * Quick helper to log a metric
     *
     * @throws \MLflow\Exception\MLflowException
     */
    function mlflow_log_metric(string $runId, string $key, float|int $value, ?int $step = null): void
    {
        mlflow()->runs()->logMetric($runId, $key, $value, null, $step);
    }
}

if (! function_exists('mlflow_log_param')) {
    /**
     * Quick helper to log a parameter
     *
     * @throws \MLflow\Exception\MLflowException
     */
    function mlflow_log_param(string $runId, string $key, string $value): void
    {
        mlflow()->runs()->logParameter($runId, $key, $value);
    }
}
