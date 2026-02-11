<?php

declare(strict_types=1);

namespace MLflow\Api;

use MLflow\Exception\MLflowException;
use MLflow\Model\Trace;
use MLflow\Model\TraceInfo;
use MLflow\Util\ResponseValidator;

/**
 * API for managing MLflow traces
 * Uses V3 API endpoints (/api/3.0/mlflow/traces)
 */
class TraceApi extends BaseApi
{
    /**
     * Get trace by ID
     *
     * @param string $traceId Trace ID
     * @return Trace
     * @throws MLflowException
     */
    public function getTrace(string $traceId): Trace
    {
        $response = $this->post('mlflow/traces/get', [
            'trace_id' => $traceId,
        ]);

        $traceData = $response['trace'] ?? $response;
        if (!is_array($traceData)) {
            throw new MLflowException('Invalid trace data in response');
        }

        return Trace::fromArray($traceData);
    }

    /**
     * Search traces
     *
     * @param string[] $experimentIds
     * @param string[] $orderBy
     * @return array{traces: Trace[], next_page_token: string|null}
     */
    public function searchTraces(
        array $experimentIds,
        ?string $filterString = null,
        int $maxResults = 1000,
        ?array $orderBy = null,
        ?string $pageToken = null,
        ?string $runId = null,
        ?string $modelId = null
    ): array {
        $params = [
            'experiment_ids' => $experimentIds,
            'max_results' => $maxResults,
        ];

        if ($filterString) {
            $params['filter'] = $filterString;
        }

        if ($orderBy) {
            $params['order_by'] = $orderBy;
        }

        if ($pageToken) {
            $params['page_token'] = $pageToken;
        }

        if ($runId) {
            $params['run_id'] = $runId;
        }

        if ($modelId) {
            $params['model_id'] = $modelId;
        }

        $response = $this->post('mlflow/traces/search', $params);

        $traces = [];
        if (isset($response['traces']) && is_array($response['traces'])) {
            foreach ($response['traces'] as $traceData) {
                if (is_array($traceData)) {
                    $traces[] = Trace::fromArray($traceData);
                }
            }
        }

        $nextPageToken = $response['next_page_token'] ?? null;
        return [
            'traces' => $traces,
            'next_page_token' => is_string($nextPageToken) ? $nextPageToken : null,
        ];
    }

    /**
     * Log a complete trace (end the trace)
     *
     * Note: In MLflow Python, this is called at the END of a trace
     *
     * @param Trace $trace Trace to log
     * @return TraceInfo
     * @throws MLflowException
     */
    public function logTrace(Trace $trace): TraceInfo
    {
        $response = $this->post('mlflow/traces', [
            'trace' => $trace->toArray(),
        ]);

        $traceInfoData = $response['trace_info'] ?? $response;
        if (!is_array($traceInfoData)) {
            throw new MLflowException('Invalid trace_info data in response');
        }

        return TraceInfo::fromArray($traceInfoData);
    }

    /**
     * Delete traces
     *
     * @param array<string> $traceIds Trace IDs to delete
     * @param string $experimentId Experiment ID
     * @param int $maxTraces Maximum number of traces to delete
     * @return int Number of traces deleted
     */
    public function deleteTraces(
        array $traceIds,
        string $experimentId,
        int $maxTraces = 100
    ): int {
        $response = $this->delete('mlflow/traces', [
            'experiment_id' => $experimentId,
            'trace_ids' => $traceIds,
            'max_traces' => $maxTraces,
        ]);

        return ResponseValidator::requireInt($response, 'traces_deleted');
    }

    /**
     * Set trace tag
     */
    public function setTraceTag(string $traceId, string $key, string $value): void
    {
        $this->post('mlflow/traces/set-tag', [
            'trace_id' => $traceId,
            'key' => $key,
            'value' => $value,
        ]);
    }

    /**
     * Delete trace tag
     */
    public function deleteTraceTag(string $traceId, string $key): void
    {
        $this->delete('mlflow/traces/delete-tag', [
            'trace_id' => $traceId,
            'key' => $key,
        ]);
    }
}
