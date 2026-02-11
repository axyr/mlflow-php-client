<?php

declare(strict_types=1);

namespace MLflow\Contracts;

use MLflow\Model\Metric;

/**
 * Contract for Metric API
 */
interface MetricApiContract
{
    /**
     * Get metric history for a run
     *
     * @param string $runId     The run ID
     * @param string $metricKey The metric key
     *
     * @return array<Metric> Array of metric values with timestamps
     */
    public function getHistory(string $runId, string $metricKey): array;
}
