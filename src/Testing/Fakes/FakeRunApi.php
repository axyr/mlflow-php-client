<?php

declare(strict_types=1);

namespace MLflow\Testing\Fakes;

use MLflow\Enum\RunStatus;
use MLflow\Enum\ViewType;
use MLflow\Model\Run;
use MLflow\Model\RunData;
use MLflow\Model\RunInfo;

/**
 * Fake implementation of RunApi for testing
 */
class FakeRunApi
{
    /** @var array<string, array<string, mixed>> */
    private array $runs = [];

    private int $counter = 1;

    public function __construct(
        private readonly MLflowFake $fake
    ) {}

    /**
     * Create a new run
     *
     * @param string                $experimentId Experiment ID
     * @param string|null           $userId       User ID
     * @param string|null           $runName      Run name
     * @param array<string, string> $tags         Tags
     * @param int|null              $startTime    Start time
     */
    public function create(
        string $experimentId,
        ?string $userId = null,
        ?string $runName = null,
        array $tags = [],
        ?int $startTime = null
    ): Run {
        $runId = 'fake-run-' . $this->counter++;
        $startTime = $startTime ?? (int) (microtime(true) * 1000);

        $runData = [
            'experiment_id' => $experimentId,
            'run_id' => $runId,
            'user_id' => $userId ?? 'fake-user',
            'run_name' => $runName ?? 'fake-run',
            'status' => RunStatus::RUNNING->value,
            'start_time' => $startTime,
            'tags' => $tags,
            'metrics' => [],
            'params' => [],
        ];

        $this->runs[$runId] = $runData;

        // Record in fake
        $this->fake->recordRun($runData);

        return $this->buildRun($runData);
    }

    /**
     * Get a run by ID
     */
    public function getById(string $runId): Run
    {
        $data = $this->runs[$runId] ?? null;

        if ($data === null) {
            throw new \MLflow\Exception\NotFoundException("Run {$runId} not found");
        }

        return $this->buildRun($data);
    }

    /**
     * Search for runs
     *
     * @param array<string>      $experimentIds Experiment IDs
     * @param string|null        $filter        Filter
     * @param ViewType           $runViewType   View type
     * @param int|null           $maxResults    Max results
     * @param array<string>|null $orderBy       Order by
     * @param string|null        $pageToken     Page token
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
    ): array {
        $runs = [];

        foreach ($this->runs as $data) {
            if (empty($experimentIds) || in_array($data['experiment_id'], $experimentIds, true)) {
                $runs[] = $this->buildRun($data);
            }
        }

        return [
            'runs' => $runs,
            'next_page_token' => null,
        ];
    }

    /**
     * Log a metric for a run
     */
    public function logMetric(
        string $runId,
        string $key,
        float $value,
        ?int $timestamp = null,
        ?int $step = null
    ): void {
        if (! isset($this->runs[$runId])) {
            throw new \MLflow\Exception\NotFoundException("Run {$runId} not found");
        }

        $this->fake->recordMetric($runId, $key, $value, $step);

        /** @phpstan-ignore offsetAccess.nonOffsetAccessible */
        if (! isset($this->runs[$runId]['metrics'])) {
            $this->runs[$runId]['metrics'] = [];
        }

        /** @phpstan-ignore offsetAccess.nonOffsetAccessible */
        $this->runs[$runId]['metrics'][$key] = $value;
    }

    /**
     * Log a parameter for a run
     */
    public function logParameter(string $runId, string $key, string $value): void
    {
        if (! isset($this->runs[$runId])) {
            throw new \MLflow\Exception\NotFoundException("Run {$runId} not found");
        }

        $this->fake->recordParam($runId, $key, $value);

        /** @phpstan-ignore offsetAccess.nonOffsetAccessible */
        if (! isset($this->runs[$runId]['params'])) {
            $this->runs[$runId]['params'] = [];
        }

        /** @phpstan-ignore offsetAccess.nonOffsetAccessible */
        $this->runs[$runId]['params'][$key] = $value;
    }

    /**
     * Log batch data
     *
     * @param string                                                                   $runId   Run ID
     * @param array<array{key: string, value: float|int, timestamp?: int, step?: int}> $metrics Metrics
     * @param array<string, string>                                                    $params  Params
     * @param array<string, string>                                                    $tags    Tags
     */
    public function logBatch(
        string $runId,
        array $metrics = [],
        array $params = [],
        array $tags = []
    ): void {
        foreach ($metrics as $metric) {
            $this->logMetric(
                $runId,
                $metric['key'],
                (float) $metric['value'],
                $metric['timestamp'] ?? null,
                $metric['step'] ?? null
            );
        }

        foreach ($params as $key => $value) {
            $this->logParameter($runId, $key, $value);
        }

        foreach ($tags as $key => $value) {
            $this->setTag($runId, $key, $value);
        }
    }

    /**
     * Set a tag on a run
     */
    public function setTag(string $runId, string $key, string $value): void
    {
        if (! isset($this->runs[$runId])) {
            throw new \MLflow\Exception\NotFoundException("Run {$runId} not found");
        }

        if (! isset($this->runs[$runId]['tags']) || ! is_array($this->runs[$runId]['tags'])) {
            $this->runs[$runId]['tags'] = [];
        }

        /** @var array<string, string> $tags */
        $tags = $this->runs[$runId]['tags'];
        $tags[$key] = $value;
        $this->runs[$runId]['tags'] = $tags;
    }

    /**
     * Update a run
     */
    public function update(
        string $runId,
        ?RunStatus $status = null,
        ?int $endTime = null,
        ?string $runName = null
    ): void {
        if (! isset($this->runs[$runId])) {
            throw new \MLflow\Exception\NotFoundException("Run {$runId} not found");
        }

        if ($status !== null) {
            $this->runs[$runId]['status'] = $status->value;
        }

        if ($endTime !== null) {
            $this->runs[$runId]['end_time'] = $endTime;
        }

        if ($runName !== null) {
            $this->runs[$runId]['run_name'] = $runName;
        }
    }

    /**
     * Set terminated status for a run
     */
    public function setTerminated(
        string $runId,
        RunStatus $status = RunStatus::FINISHED,
        ?int $endTime = null
    ): void {
        $this->update($runId, $status, $endTime ?? (int) (microtime(true) * 1000));
    }

    /**
     * Delete a run
     */
    public function deleteRun(string $runId): void
    {
        if (isset($this->runs[$runId])) {
            $this->runs[$runId]['lifecycle_stage'] = 'deleted';
        }
    }

    /**
     * Restore a deleted run
     */
    public function restore(string $runId): void
    {
        if (isset($this->runs[$runId])) {
            $this->runs[$runId]['lifecycle_stage'] = 'active';
        }
    }

    /**
     * Delete a tag from a run
     */
    public function deleteTag(string $runId, string $key): void
    {
        if (isset($this->runs[$runId]['tags']) && is_array($this->runs[$runId]['tags'])) {
            /** @var array<string, string> $tags */
            $tags = $this->runs[$runId]['tags'];
            unset($tags[$key]);
            $this->runs[$runId]['tags'] = $tags;
        }
    }

    /**
     * Build a Run object from array data
     *
     * @param array<string, mixed> $data
     *
     * @phpstan-ignore-next-line
     */
    private function buildRun(array $data): Run
    {
        $runId = $data['run_id'] ?? '';
        $experimentId = $data['experiment_id'] ?? '';
        $statusValue = $data['status'] ?? 'RUNNING';
        $startTime = $data['start_time'] ?? 0;
        $userId = $data['user_id'] ?? 'fake-user';
        $runName = $data['run_name'] ?? 'fake-run';

        /** @var string $runId */
        /** @var string $experimentId */
        /** @var int $startTime */
        /** @var string $userId */
        /** @var string $runName */
        $info = new RunInfo(
            runId: $runId,
            experimentId: $experimentId,
            status: is_string($statusValue) ? RunStatus::from($statusValue) : RunStatus::RUNNING,
            startTime: $startTime,
            userId: $userId,
            runName: $runName
        );

        $runData = new RunData(
            metrics: null,
            params: null,
            tags: null
        );

        return new Run($info, $runData);
    }
}
