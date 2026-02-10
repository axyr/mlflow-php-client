<?php

declare(strict_types=1);

namespace MLflow\Model;

class MlflowExperimentLocation extends TraceLocation
{
    public function __construct(
        private string $experimentId
    ) {
    }

    public function getExperimentId(): string
    {
        return $this->experimentId;
    }

    public function toArray(): array
    {
        return [
            'type' => 'MLFLOW_EXPERIMENT',
            'experiment_id' => $this->experimentId,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            experimentId: $data['experiment_id']
        );
    }
}
