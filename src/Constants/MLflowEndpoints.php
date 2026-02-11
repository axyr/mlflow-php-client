<?php

declare(strict_types=1);

namespace MLflow\Constants;

/**
 * MLflow REST API endpoint constants
 */
final class MLflowEndpoints
{
    // Experiments
    public const EXPERIMENTS_CREATE = 'mlflow/experiments/create';

    public const EXPERIMENTS_GET = 'mlflow/experiments/get';

    public const EXPERIMENTS_GET_BY_NAME = 'mlflow/experiments/get-by-name';

    public const EXPERIMENTS_DELETE = 'mlflow/experiments/delete';

    public const EXPERIMENTS_RESTORE = 'mlflow/experiments/restore';

    public const EXPERIMENTS_UPDATE = 'mlflow/experiments/update';

    public const EXPERIMENTS_SEARCH = 'mlflow/experiments/search';

    public const EXPERIMENTS_SET_TAG = 'mlflow/experiments/set-experiment-tag';

    public const EXPERIMENTS_DELETE_TAG = 'mlflow/experiments/delete-experiment-tag';

    // Runs
    public const RUNS_CREATE = 'mlflow/runs/create';

    public const RUNS_GET = 'mlflow/runs/get';

    public const RUNS_UPDATE = 'mlflow/runs/update';

    public const RUNS_DELETE = 'mlflow/runs/delete';

    public const RUNS_RESTORE = 'mlflow/runs/restore';

    public const RUNS_SEARCH = 'mlflow/runs/search';

    public const RUNS_LOG_METRIC = 'mlflow/runs/log-metric';

    public const RUNS_LOG_BATCH = 'mlflow/runs/log-batch';

    public const RUNS_LOG_PARAMETER = 'mlflow/runs/log-parameter';

    public const RUNS_SET_TAG = 'mlflow/runs/set-tag';

    public const RUNS_DELETE_TAG = 'mlflow/runs/delete-tag';

    // Metrics
    public const METRICS_GET_HISTORY = 'mlflow/metrics/get-history';

    public const METRICS_GET_HISTORY_BULK = 'mlflow/metrics/get-history-bulk';

    // Model Registry
    public const MODEL_REGISTRY_CREATE = 'mlflow/registered-models/create';

    public const MODEL_REGISTRY_GET = 'mlflow/registered-models/get';

    public const MODEL_REGISTRY_UPDATE = 'mlflow/registered-models/update';

    public const MODEL_REGISTRY_DELETE = 'mlflow/registered-models/delete';

    public const MODEL_REGISTRY_RENAME = 'mlflow/registered-models/rename';

    public const MODEL_REGISTRY_SEARCH = 'mlflow/registered-models/search';

    public const MODEL_REGISTRY_GET_LATEST_VERSIONS = 'mlflow/registered-models/get-latest-versions';

    public const MODEL_REGISTRY_SET_TAG = 'mlflow/registered-models/set-tag';

    public const MODEL_REGISTRY_DELETE_TAG = 'mlflow/registered-models/delete-tag';

    // Model Versions
    public const MODEL_VERSIONS_CREATE = 'mlflow/model-versions/create';

    public const MODEL_VERSIONS_GET = 'mlflow/model-versions/get';

    public const MODEL_VERSIONS_UPDATE = 'mlflow/model-versions/update';

    public const MODEL_VERSIONS_DELETE = 'mlflow/model-versions/delete';

    public const MODEL_VERSIONS_SEARCH = 'mlflow/model-versions/search';

    public const MODEL_VERSIONS_TRANSITION_STAGE = 'mlflow/model-versions/transition-stage';

    public const MODEL_VERSIONS_SET_TAG = 'mlflow/model-versions/set-tag';

    public const MODEL_VERSIONS_DELETE_TAG = 'mlflow/model-versions/delete-tag';

    // Model Aliases
    public const MODEL_ALIASES_SET = 'mlflow/registered-models/alias';

    public const MODEL_ALIASES_DELETE = 'mlflow/registered-models/alias';

    public const MODEL_ALIASES_GET_BY_ALIAS = 'mlflow/registered-models/get-by-alias';

    // Artifacts
    public const ARTIFACTS_LIST = 'mlflow/artifacts/list';

    public const ARTIFACTS_DOWNLOAD = 'mlflow-artifacts/artifacts';

    // Traces (MLflow 3.0+)
    public const TRACES_DELETE = 'traces/delete-traces';

    public const TRACES_GET = 'traces/get-trace';

    // Webhooks
    public const WEBHOOKS_CREATE = 'mlflow/webhooks/create';

    public const WEBHOOKS_GET = 'mlflow/webhooks/get';

    public const WEBHOOKS_UPDATE = 'mlflow/webhooks/update';

    public const WEBHOOKS_DELETE = 'mlflow/webhooks/delete';

    public const WEBHOOKS_LIST = 'mlflow/webhooks/list';

    // Datasets
    public const DATASETS_CREATE = 'mlflow/datasets/create';

    public const DATASETS_GET = 'mlflow/datasets/get';

    // Prompts
    public const PROMPTS_CREATE = 'mlflow/prompts/create';

    public const PROMPTS_GET = 'mlflow/prompts/get';

    private function __construct()
    {
        // Prevent instantiation
    }
}
