<?php

declare(strict_types=1);

namespace MLflow\Api;

use MLflow\Model\Metric;
use MLflow\Model\MetricHistory;
use MLflow\Exception\MLflowException;

/**
 * Complete API for managing MLflow metrics
 * Implements all REST API endpoints from MLflow official documentation
 */
class MetricApi extends BaseApi
{
    /**
     * Get metric history for a run
     *
     * @param string $runId The run ID
     * @param string $metricKey The metric key
     * @return array<Metric> Array of metric values with timestamps
     * @throws MLflowException
     */
    public function getHistory(string $runId, string $metricKey): array
    {
        $response = $this->get('mlflow/metrics/get-history', [
            'run_id' => $runId,
            'metric_key' => $metricKey,
        ]);

        $metrics = [];
        if (isset($response['metrics']) && is_array($response['metrics'])) {
            foreach ($response['metrics'] as $metricData) {
                if (is_array($metricData) && isset($metricData['key'], $metricData['value'], $metricData['timestamp'])) {
                    /** @var array{key: string, value: float|int|string, timestamp: int|string, step?: int|string} $metricData */
                    $metrics[] = Metric::fromArray($metricData);
                }
            }
        }

        return $metrics;
    }

    /**
     * Get metric history for multiple metrics in bulk
     *
     * @param string $runId The run ID
     * @param array<string> $metricKeys Array of metric keys
     * @return array<string, array<Metric>> Associative array of metric histories by key
     * @throws MLflowException
     */
    public function getHistoryBulk(string $runId, array $metricKeys): array
    {
        $response = $this->post('mlflow/metrics/get-history-bulk', [
            'run_id' => $runId,
            'metric_keys' => $metricKeys,
        ]);

        $histories = [];
        if (isset($response['metrics']) && is_array($response['metrics'])) {
            foreach ($response['metrics'] as $key => $metricHistory) {
                $metrics = [];
                if (is_array($metricHistory)) {
                    foreach ($metricHistory as $metricData) {
                        if (is_array($metricData) && isset($metricData['key'], $metricData['value'], $metricData['timestamp'])) {
                            /** @var array{key: string, value: float|int|string, timestamp: int|string, step?: int|string} $metricData */
                            $metrics[] = Metric::fromArray($metricData);
                        }
                    }
                }
                $histories[(string)$key] = $metrics;
            }
        }

        return $histories;
    }

    /**
     * DEPRECATED: Use RunApi::logMetric() instead
     * Log a single metric for a run
     *
     * @deprecated Use RunApi::logMetric() method instead
     * @param string $runId The run ID
     * @param string $key Metric key
     * @param float $value Metric value
     * @param int|null $timestamp Timestamp (milliseconds since epoch)
     * @param int|null $step Step number
     * @return void
     * @throws MLflowException
     */
    public function log(string $runId, string $key, float $value, ?int $timestamp = null, ?int $step = null): void
    {
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
     * DEPRECATED: Use RunApi::logBatch() instead
     * Log multiple metrics for a run
     *
     * @deprecated Use RunApi::logBatch() method instead
     * @param string $runId The run ID
     * @param array<array{key: string, value: float|int, timestamp?: int, step?: int}> $metrics Array of metrics
     * @return void
     * @throws MLflowException
     */
    public function logBatch(string $runId, array $metrics): void
    {
        $formattedMetrics = [];
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

            $formattedMetrics[] = $formattedMetric;
        }

        $this->post('mlflow/runs/log-batch', [
            'run_id' => $runId,
            'metrics' => $formattedMetrics,
        ]);
    }

    /**
     * DEPRECATED: Use RunApi::logParameter() instead
     * Log a parameter for a run
     *
     * @deprecated Use RunApi::logParameter() method instead
     * @param string $runId The run ID
     * @param string $key Parameter key
     * @param string $value Parameter value
     * @return void
     * @throws MLflowException
     */
    public function logParam(string $runId, string $key, string $value): void
    {
        $this->post('mlflow/runs/log-parameter', [
            'run_id' => $runId,
            'key' => $key,
            'value' => $value,
        ]);
    }

    /**
     * DEPRECATED: Use RunApi::logBatch() instead
     * Log multiple parameters for a run
     *
     * @deprecated Use RunApi::logBatch() method instead
     * @param string $runId The run ID
     * @param array<string, string> $params Associative array of parameters
     * @return void
     * @throws MLflowException
     */
    public function logParams(string $runId, array $params): void
    {
        $formattedParams = [];

        foreach ($params as $key => $value) {
            $formattedParams[] = [
                'key' => (string) $key,
                'value' => (string) $value,
            ];
        }

        $this->post('mlflow/runs/log-batch', [
            'run_id' => $runId,
            'params' => $formattedParams,
        ]);
    }

    /**
     * DEPRECATED: Use RunApi::setTag() instead
     * Set a tag on a run
     *
     * @deprecated Use RunApi::setTag() method instead
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
     * DEPRECATED: Use RunApi::setTag() instead
     * Set multiple tags on a run
     *
     * @deprecated Use RunApi::logBatch() method instead
     * @param string $runId The run ID
     * @param array<string, string> $tags Associative array of tags
     * @return void
     * @throws MLflowException
     */
    public function setTags(string $runId, array $tags): void
    {
        foreach ($tags as $key => $value) {
            $this->setTag($runId, (string) $key, (string) $value);
        }
    }
}