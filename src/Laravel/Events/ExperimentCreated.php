<?php

declare(strict_types=1);

namespace MLflow\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use MLflow\Model\Experiment;

/**
 * Event fired when an MLflow experiment is created
 *
 * This event is dispatched after a new experiment has been successfully
 * created in the MLflow tracking server.
 */
class ExperimentCreated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Experiment $experiment The created experiment
     */
    public function __construct(
        public readonly Experiment $experiment
    ) {}
}
