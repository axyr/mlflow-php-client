<?php

declare(strict_types=1);

namespace MLflow\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an MLflow run is completed
 *
 * This event is dispatched after a run has been marked as completed
 * with a terminal status (FINISHED, FAILED, or KILLED).
 */
class RunCompleted
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param string $runId  The completed run ID
     * @param string $status The final status of the run
     */
    public function __construct(
        public readonly string $runId,
        public readonly string $status
    ) {}
}
