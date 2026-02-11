<?php

declare(strict_types=1);

namespace MLflow\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use MLflow\Model\Run;

/**
 * Event fired when an MLflow run is started
 *
 * This event is dispatched after a new run has been successfully
 * created and started in the MLflow tracking server.
 */
class RunStarted
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Run $run The started run
     */
    public function __construct(
        public readonly Run $run
    ) {}
}
