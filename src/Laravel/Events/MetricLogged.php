<?php

declare(strict_types=1);

namespace MLflow\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a metric is logged to an MLflow run
 *
 * This event is dispatched after a metric has been successfully
 * logged to a run in the MLflow tracking server.
 */
class MetricLogged
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param string   $runId     The run ID
     * @param string   $key       The metric key
     * @param float    $value     The metric value
     * @param int|null $timestamp The timestamp in milliseconds
     * @param int|null $step      The step number
     */
    public function __construct(
        public readonly string $runId,
        public readonly string $key,
        public readonly float $value,
        public readonly ?int $timestamp = null,
        public readonly ?int $step = null
    ) {}
}
