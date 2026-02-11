<?php

declare(strict_types=1);

namespace MLflow\Api;

use MLflow\Enum\RunStatus;
use MLflow\Enum\ViewType;
use MLflow\Model\Run;
use MLflow\Model\RunInfo;
use MLflow\Model\RunData;
use MLflow\Exception\MLflowException;

/**
 * Complete API for managing MLflow runs
 * Implements all REST API endpoints from MLflow official documentation
 */
class RunApi extends BaseApi
{
    /**
     * Create a new run
     *
     * @param string $experimentId The experiment ID
     * @param string|null $userId Optional user ID
     * @param string|null $runName Optional run name
     * @param array<string, string> $tags Optional tags for the run
     * @param int|null $startTime Optional start time (milliseconds since epoch)
     * @return Run The created run
     * @throws MLflowException
     */
    public function create(
        string $experimentId,
        ?string $userId = null,
        ?string $runName = null,
        array $tags = [],
        ?int $startTime = null
    ): Run {
        $data = ['experiment_id' => $experimentId];

        if ($userId !== null) {
            $data['user_id'] = $userId;
        }

        if ($runName !== null) {
            $data['run_name'] = $runName;
        }

        if (!empty($tags)) {
            $data['tags'] = $this->formatTags($tags);
        }

        $data['start_time'] = $startTime ?? (int) (microtime(true) * 1000);

        $response = $this->post('mlflow/runs/create', $data);

        $run = $response['run'] ?? null;
        if (!is_array($run)) {
            throw new MLflowException('Invalid run data in response');
        }

        return Run::fromArray($run);
    }

    /**
     * Get a run by ID
     *
     * @param string $runId The run ID
     * @return Run The run
     * @throws MLflowException
     */
    public function getById(string $runId): Run
    {
        $response = $this->get('mlflow/runs/get', ['run_id' => $runId]);

        $run = $response['run'] ?? null;
        if (!is_array($run)) {
            throw new MLflowException('Invalid run data in response');
        }

        return Run::fromArray($run);
    }

    /**
     * Search for runs
     *
     * @param array<string> $experimentIds List of experiment IDs to search in
     * @param string|null $filter Filter string (e.g., "metrics.accuracy > 0.9")
     * @param ViewType $runViewType Run view type
     * @param int|null $maxResults Maximum number of runs to return
     * @param array<string>|null $orderBy List of columns to order by (e.g., ["metrics.accuracy DESC"])
     * @param string|null $pageToken Pagination token
     * @return array{runs: array<Run>, next_page_token: string|null} Array with runs and next_page_token
     * @throws MLflowException
     */
    public function search(
        array $experimentIds = [],
        ?string $filter = null,
        ViewType $runViewType = ViewType::ACTIVE_ONLY,
        ?int $maxResults = null,
        ?array $orderBy = null,
        ?string $pageToken = null
    ): array {
        $data = [
            'experiment_ids' => $experimentIds,
            'run_view_type' => $runViewType->value,
        ];

        if ($filter !== null) {
            $data['filter'] = $filter;
        }

        if ($maxResults !== null) {
            $data['max_results'] = $maxResults;
        }

        if ($orderBy !== null) {
            $data['order_by'] = $orderBy;
        }

        if ($pageToken !== null) {
            $data['page_token'] = $pageToken;
        }

        $response = $this->post('mlflow/runs/search', $data);

        $runs = [];
        if (isset($response['runs']) && is_array($response['runs'])) {
            foreach ($response['runs'] as $runData) {
                if (is_array($runData)) {
                    $runs[] = Run::fromArray($runData);
                }
            }
        }

        $nextPageToken = $response['next_page_token'] ?? null;

        return [
            'runs' => $runs,
            'next_page_token' => is_string($nextPageToken) ? $nextPageToken : null,
        ];
    }

    /**
     * Update a run
     *
     * @param string $runId The run ID
     * @param RunStatus|null $status New status
     * @param int|null $endTime End time (milliseconds since epoch)
     * @param string|null $runName New name for the run
     * @return void
     * @throws MLflowException
     */
    public function update(
        string $runId,
        ?RunStatus $status = null,
        ?int $endTime = null,
        ?string $runName = null
    ): void {
        $data = ['run_id' => $runId];

        if ($status !== null) {
            $data['status'] = $status->value;
        }

        if ($endTime !== null) {
            $data['end_time'] = $endTime;
        }

        if ($runName !== null) {
            $data['run_name'] = $runName;
        }

        $this->post('mlflow/runs/update', $data);
    }

    /**
     * Delete a run
     *
     * @param string $runId The run ID
     * @return void
     * @throws MLflowException
     */
    public function deleteRun(string $runId): void
    {
        $this->post('mlflow/runs/delete', ['run_id' => $runId]);
    }

    /**
     * Restore a deleted run
     *
     * @param string $runId The run ID
     * @return void
     * @throws MLflowException
     */
    public function restore(string $runId): void
    {
        $this->post('mlflow/runs/restore', ['run_id' => $runId]);
    }

    /**
     * Set a tag on a run
     *
     * @param string $runId The run ID
     * @param string $key Tag key
     * @param string $value Tag value
     * @return void
     * @throws MLflowException
     */
    public function setTag(string $runId, string $key, string $value): void
    {
        $this->post('mlflow/runs/set-tag', [
            'run_id' => $runId,
            'key' => $key,
            'value' => $value,
        ]);
    }

    /**
     * Delete a tag from a run
     *
     * @param string $runId The run ID
     * @param string $key Tag key to delete
     * @return void
     * @throws MLflowException
     */
    public function deleteTag(string $runId, string $key): void
    {
        $this->post('mlflow/runs/delete-tag', [
            'run_id' => $runId,
            'key' => $key,
        ]);
    }

    /**
     * Log a metric for a run
     *
     * @param string $runId The run ID
     * @param string $key Metric key
     * @param float $value Metric value
     * @param int|null $timestamp Timestamp (milliseconds since epoch)
     * @param int|null $step Step number
     * @return void
     * @throws MLflowException
     */
    public function logMetric(
        string $runId,
        string $key,
        float $value,
        ?int $timestamp = null,
        ?int $step = null
    ): void {
        $data = [
            'run_id' => $runId,
            'key' => $key,
            'value' => $value,
            'timestamp' => $timestamp ?? (int) (microtime(true) * 1000),
        ];

        if ($step !== null) {
            $data['step'] = $step;
        }

        $this->post('mlflow/runs/log-metric', $data);
    }

    /**
     * Log a parameter for a run
     *
     * @param string $runId The run ID
     * @param string $key Parameter key
     * @param string $value Parameter value
     * @return void
     * @throws MLflowException
     */
    public function logParameter(string $runId, string $key, string $value): void
    {
        $this->post('mlflow/runs/log-parameter', [
            'run_id' => $runId,
            'key' => $key,
            'value' => $value,
        ]);
    }

    /**
     * Log multiple metrics, parameters, and tags in a single request
     *
     * @param string $runId The run ID
     * @param array<array{key: string, value: float|int, timestamp?: int, step?: int}> $metrics Array of metrics to log
     * @param array<string, string> $params Array of parameters to log
     * @param array<string, string> $tags Array of tags to set
     * @return void
     * @throws MLflowException
     */
    public function logBatch(
        string $runId,
        array $metrics = [],
        array $params = [],
        array $tags = []
    ): void {
        $data = ['run_id' => $runId];

        if (!empty($metrics)) {
            $data['metrics'] = $this->formatMetrics($metrics);
        }

        if (!empty($params)) {
            $data['params'] = $this->formatParams($params);
        }

        if (!empty($tags)) {
            $data['tags'] = $this->formatTags($tags);
        }

        $this->post('mlflow/runs/log-batch', $data);
    }

    /**
     * Log a model to a run
     *
     * @param string $runId The run ID
     * @param string $artifactPath Path within the run's artifact directory
     * @param array<string, mixed> $flavors Model flavors (e.g., {"python_function": {...}})
     * @param string|null $modelJson Optional model JSON
     * @param string|null $signatureJson Optional signature JSON
     * @return void
     * @throws MLflowException
     */
    public function logModel(
        string $runId,
        string $artifactPath,
        array $flavors,
        ?string $modelJson = null,
        ?string $signatureJson = null
    ): void {
        $data = [
            'run_id' => $runId,
            'artifact_path' => $artifactPath,
            'flavors' => $flavors,
        ];

        if ($modelJson !== null) {
            $data['model_json'] = $modelJson;
        }

        if ($signatureJson !== null) {
            $data['signature_json'] = $signatureJson;
        }

        $this->post('mlflow/runs/log-model', $data);
    }

    /**
     * Log inputs (datasets) for a run
     *
     * @param string $runId The run ID
     * @param array<array{
     *     dataset: array<string, mixed>,
     *     tags?: array<array{key: string, value: string}>
     * }> $datasets Array of dataset inputs
     * @return void
     * @throws MLflowException
     */
    public function logInputs(string $runId, array $datasets): void
    {
        $this->post('mlflow/runs/log-inputs', [
            'run_id' => $runId,
            'datasets' => $datasets,
        ]);
    }

    /**
     * Set terminated status for a run
     *
     * @param string $runId The run ID
     * @param RunStatus $status Final status (must be terminal: FINISHED, FAILED, or KILLED)
     * @param int|null $endTime End time (milliseconds since epoch)
     * @return void
     * @throws MLflowException
     */
    public function setTerminated(
        string $runId,
        RunStatus $status = RunStatus::FINISHED,
        ?int $endTime = null
    ): void {
        if (!$status->isTerminal()) {
            throw new MLflowException("Status {$status->value} is not a terminal status");
        }
        $this->update($runId, $status, $endTime ?? (int) (microtime(true) * 1000));
    }

    /**
     * Format metrics for batch logging
     *
     * @param array<array{key: string, value: float|int, timestamp?: int, step?: int}> $metrics Array of metrics
     * @return array<int, array{key: string, value: float|int, timestamp: int, step?: int}> Formatted metrics
     */
    private function formatMetrics(array $metrics): array
    {
        $formatted = [];
        $timestamp = (int) (microtime(true) * 1000);

        foreach ($metrics as $metric) {
            if (!is_array($metric)) {
                continue;
            }
            $formattedMetric = [
                'key' => $metric['key'] ?? '',
                'value' => $metric['value'] ?? 0,
                'timestamp' => $metric['timestamp'] ?? $timestamp,
            ];

            if (isset($metric['step'])) {
                $formattedMetric['step'] = $metric['step'];
            }

            $formatted[] = $formattedMetric;
        }

        return $formatted;
    }

    /**
     * Get parent run (for nested runs)
     *
     * @param string $runId Child run ID
     * @return Run|null Parent run or null if no parent
     * @throws MLflowException
     */
    public function getParentRun(string $runId): ?Run
    {
        $run = $this->getById($runId);
        $tags = $run->getTags();

        // Look for parent run ID in tags
        foreach ($tags as $tag) {
            if ($tag->key === 'mlflow.parentRunId' && $tag->value !== '') {
                return $this->getById($tag->value);
            }
        }

        return null;
    }

    /**
     * Link a prompt version to a run
     *
     * @param string $runId Run ID
     * @param string $promptName Prompt name
     * @param string $promptVersion Prompt version
     * @return void
     * @throws MLflowException
     */
    public function linkPromptVersionToRun(
        string $runId,
        string $promptName,
        string $promptVersion
    ): void {
        $this->post('mlflow/runs/link-prompt-version', [
            'run_id' => $runId,
            'prompt_name' => $promptName,
            'prompt_version' => $promptVersion,
        ]);
    }

    /**
     * Link a prompt version to a model
     *
     * @param string $modelName Model name
     * @param string $modelVersion Model version
     * @param string $promptName Prompt name
     * @param string $promptVersion Prompt version
     * @return void
     * @throws MLflowException
     */
    public function linkPromptVersionToModel(
        string $modelName,
        string $modelVersion,
        string $promptName,
        string $promptVersion
    ): void {
        $this->post('mlflow/model-versions/link-prompt-version', [
            'model_name' => $modelName,
            'model_version' => $modelVersion,
            'prompt_name' => $promptName,
            'prompt_version' => $promptVersion,
        ]);
    }

    /**
     * Link multiple traces to a run
     *
     * @param string $runId Run ID
     * @param array<string> $traceIds Trace IDs to link
     * @return void
     * @throws MLflowException
     */
    public function linkTracesToRun(string $runId, array $traceIds): void
    {
        $this->post('mlflow/runs/link-traces', [
            'run_id' => $runId,
            'trace_ids' => $traceIds,
        ]);
    }

    /**
     * Format parameters for batch logging
     *
     * @param array<string, string> $params Associative array of parameters
     * @return array<int, array{key: string, value: string}> Formatted parameters
     */
    private function formatParams(array $params): array
    {
        $formatted = [];
        foreach ($params as $key => $value) {
            $formatted[] = [
                'key' => (string) $key,
                'value' => (string) $value,
            ];
        }
        return $formatted;
    }
}
