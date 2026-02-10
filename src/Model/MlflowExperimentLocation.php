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

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'type' => 'MLFLOW_EXPERIMENT',
            'experiment_id' => $this->experimentId,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            experimentId: (string) ($data['experiment_id'] ?? '')
        );
    }
}
