<?php

declare(strict_types=1);

namespace MLflow\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use MLflow\Model\RegisteredModel;

/**
 * Event fired when a model is registered in MLflow
 *
 * This event is dispatched after a new model has been successfully
 * registered in the MLflow model registry.
 */
class ModelRegistered
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param RegisteredModel $model The registered model
     */
    public function __construct(
        public readonly RegisteredModel $model
    ) {}
}
