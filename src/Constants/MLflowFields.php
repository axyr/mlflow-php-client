<?php

declare(strict_types=1);

namespace MLflow\Constants;

/**
 * MLflow API field name constants
 */
final class MLflowFields
{
    // Common identifiers
    public const RUN_ID = 'run_id';
    public const RUN_UUID = 'run_uuid';
    public const EXPERIMENT_ID = 'experiment_id';
    public const USER_ID = 'user_id';
    public const RUN_NAME = 'run_name';

    // Run fields
    public const START_TIME = 'start_time';
    public const END_TIME = 'end_time';
    public const STATUS = 'status';
    public const LIFECYCLE_STAGE = 'lifecycle_stage';
    public const ARTIFACT_URI = 'artifact_uri';

    // Metric fields
    public const KEY = 'key';
    public const VALUE = 'value';
    public const TIMESTAMP = 'timestamp';
    public const STEP = 'step';

    // Experiment fields
    public const NAME = 'name';
    public const ARTIFACT_LOCATION = 'artifact_location';
    public const CREATION_TIME = 'creation_time';
    public const LAST_UPDATE_TIME = 'last_update_time';

    // Model Registry fields
    public const MODEL_NAME = 'name';
    public const MODEL_VERSION = 'version';
    public const MODEL_STAGE = 'stage';
    public const MODEL_SOURCE = 'source';
    public const MODEL_DESCRIPTION = 'description';

    // Tag/Param fields
    public const TAGS = 'tags';
    public const PARAMS = 'params';
    public const METRICS = 'metrics';

    // Search/Filter fields
    public const FILTER = 'filter';
    public const ORDER_BY = 'order_by';
    public const MAX_RESULTS = 'max_results';
    public const PAGE_TOKEN = 'page_token';

    private function __construct()
    {
        // Prevent instantiation
    }
}
