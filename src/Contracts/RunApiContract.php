<?php

declare(strict_types=1);

namespace MLflow\Contracts;

use MLflow\Enum\RunStatus;
use MLflow\Enum\ViewType;
use MLflow\Model\Run;

/**
 * Contract for Run API
 */
interface RunApiContract
{
    /**
     * Create a new run
     *
     * @param string                $experimentId The experiment ID
     * @param string|null           $userId       Optional user ID
     * @param string|null           $runName      Optional run name
     * @param array<string, string> $tags         Optional tags for the run
     * @param int|null              $startTime    Optional start time (milliseconds since epoch)
     *
     * @return Run The created run
     */
    public function create(
        string $experimentId,
        ?string $userId = null,
        ?string $runName = null,
        array $tags = [],
        ?int $startTime = null
    ): Run;

    /**
     * Get a run by ID
     *
     * @param string $runId The run ID
     *
     * @return Run The run
     */
    public function getById(string $runId): Run;

    /**
     * Search for runs
     *
     * @param array<string>      $experimentIds List of experiment IDs to search in
     * @param string|null        $filter        Filter string
     * @param ViewType           $runViewType   Run view type
     * @param int|null           $maxResults    Maximum number of runs to return
     * @param array<string>|null $orderBy       List of columns to order by
     * @param string|null        $pageToken     Pagination token
     *
     * @return array{runs: array<Run>, next_page_token: string|null}
     */
    public function search(
        array $experimentIds = [],
        ?string $filter = null,
        ViewType $runViewType = ViewType::ACTIVE_ONLY,
        ?int $maxResults = null,
        ?array $orderBy = null,
        ?string $pageToken = null
    ): array;

    /**
     * Update a run
     *
     * @param string         $runId   The run ID
     * @param RunStatus|null $status  New status
     * @param int|null       $endTime End time (milliseconds since epoch)
     * @param string|null    $runName New name for the run
     */
    public function update(
        string $runId,
        ?RunStatus $status = null,
        ?int $endTime = null,
        ?string $runName = null
    ): void;

    /**
     * Delete a run
     *
     * @param string $runId The run ID
     */
    public function deleteRun(string $runId): void;

    /**
     * Restore a deleted run
     *
     * @param string $runId The run ID
     */
    public function restore(string $runId): void;

    /**
     * Set a tag on a run
     *
     * @param string $runId The run ID
     * @param string $key   Tag key
     * @param string $value Tag value
     */
    public function setTag(string $runId, string $key, string $value): void;

    /**
     * Delete a tag from a run
     *
     * @param string $runId The run ID
     * @param string $key   Tag key to delete
     */
    public function deleteTag(string $runId, string $key): void;

    /**
     * Log a metric for a run
     *
     * @param string   $runId     The run ID
     * @param string   $key       Metric key
     * @param float    $value     Metric value
     * @param int|null $timestamp Timestamp (milliseconds since epoch)
     * @param int|null $step      Step number
     */
    public function logMetric(
        string $runId,
        string $key,
        float $value,
        ?int $timestamp = null,
        ?int $step = null
    ): void;

    /**
     * Log a parameter for a run
     *
     * @param string $runId The run ID
     * @param string $key   Parameter key
     * @param string $value Parameter value
     */
    public function logParameter(string $runId, string $key, string $value): void;

    /**
     * Log multiple metrics, parameters, and tags in a single request
     *
     * @param string                                                                   $runId   The run ID
     * @param array<array{key: string, value: float|int, timestamp?: int, step?: int}> $metrics Array of metrics to log
     * @param array<string, string>                                                    $params  Array of parameters to log
     * @param array<string, string>                                                    $tags    Array of tags to set
     */
    public function logBatch(
        string $runId,
        array $metrics = [],
        array $params = [],
        array $tags = []
    ): void;

    /**
     * Set terminated status for a run
     *
     * @param string    $runId   The run ID
     * @param RunStatus $status  Final status (must be terminal: FINISHED, FAILED, or KILLED)
     * @param int|null  $endTime End time (milliseconds since epoch)
     */
    public function setTerminated(
        string $runId,
        RunStatus $status = RunStatus::FINISHED,
        ?int $endTime = null
    ): void;
}
