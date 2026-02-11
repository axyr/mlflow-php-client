<?php

declare(strict_types=1);

use MLflow\MLflowClient;

if (! function_exists('mlflow')) {
    /**
     * Get the MLflow client instance
     *
     * @return MLflowClient
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
     * @param string|null $experimentId
     * @return \MLflow\Model\Experiment|null
     * @throws \MLflow\Exception\MLflowException
     */
    function mlflow_experiment(?string $experimentId = null)
    {
        if ($experimentId === null) {
            $experimentId = config('mlflow.default_experiment');
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
     * @param string $runId
     * @return \MLflow\Model\Run
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
     * @param string $runId
     * @param string $key
     * @param float|int $value
     * @param int|null $step
     * @return void
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
     * @param string $runId
     * @param string $key
     * @param string $value
     * @return void
     * @throws \MLflow\Exception\MLflowException
     */
    function mlflow_log_param(string $runId, string $key, string $value): void
    {
        mlflow()->runs()->logParameter($runId, $key, $value);
    }
}
