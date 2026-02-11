<?php

declare(strict_types=1);

namespace MLflow\Testing\Fakes;

use MLflow\Enum\ViewType;
use MLflow\Model\Experiment;

/**
 * Fake implementation of ExperimentApi for testing
 */
class FakeExperimentApi
{
    /** @var array<string, array<string, mixed>> */
    private array $experiments = [];

    private int $counter = 1;

    public function __construct(
        private readonly MLflowFake $fake
    ) {}

    /**
     * Create a new experiment
     *
     * @param string                $name             Experiment name
     * @param string|null           $artifactLocation Optional artifact location
     * @param array<string, string> $tags             Optional tags
     */
    public function create(string $name, ?string $artifactLocation = null, array $tags = []): Experiment
    {
        $experimentId = (string) $this->counter++;

        $experimentData = [
            'experiment_id' => $experimentId,
            'name' => $name,
            'artifact_location' => $artifactLocation ?? "/tmp/mlflow/{$experimentId}",
            'lifecycle_stage' => 'active',
            'tags' => $tags,
        ];

        /** @phpstan-ignore assign.propertyType */
        $this->experiments[$experimentId] = $experimentData;

        // Record in fake
        $this->fake->recordExperiment($experimentData);

        return new Experiment($experimentId, $name);
    }

    /**
     * Get an experiment by ID
     */
    public function getById(string $experimentId): Experiment
    {
        $data = $this->experiments[$experimentId] ?? null;

        if ($data === null) {
            throw new \MLflow\Exception\NotFoundException("Experiment {$experimentId} not found");
        }

        return Experiment::fromArray($data);
    }

    /**
     * Get an experiment by name
     */
    public function getByName(string $name): Experiment
    {
        foreach ($this->experiments as $data) {
            if ($data['name'] === $name) {
                return Experiment::fromArray($data);
            }
        }

        throw new \MLflow\Exception\NotFoundException("Experiment {$name} not found");
    }

    /**
     * Search experiments
     *
     * @param string|null        $filterString Filter string
     * @param int|null           $maxResults   Maximum results
     * @param string|null        $pageToken    Page token
     * @param array<string>|null $orderBy      Order by
     * @param ViewType           $viewType     View type
     *
     * @return array{experiments: array<Experiment>, next_page_token: string|null}
     */
    public function search(
        ?string $filterString = null,
        ?int $maxResults = null,
        ?string $pageToken = null,
        ?array $orderBy = null,
        ViewType $viewType = ViewType::ACTIVE_ONLY
    ): array {
        $experiments = [];

        foreach ($this->experiments as $data) {
            $experiments[] = Experiment::fromArray($data);
        }

        return [
            'experiments' => $experiments,
            'next_page_token' => null,
        ];
    }

    /**
     * Set a tag on an experiment
     */
    public function setTag(string $experimentId, string $key, string $value): void
    {
        if (! isset($this->experiments[$experimentId])) {
            throw new \MLflow\Exception\NotFoundException("Experiment {$experimentId} not found");
        }

        if (! isset($this->experiments[$experimentId]['tags']) || ! is_array($this->experiments[$experimentId]['tags'])) {
            $this->experiments[$experimentId]['tags'] = [];
        }

        /** @var array<string, string> $tags */
        $tags = $this->experiments[$experimentId]['tags'];
        $tags[$key] = $value;
        $this->experiments[$experimentId]['tags'] = $tags;
    }

    /**
     * Delete an experiment
     */
    public function deleteExperiment(string $experimentId): void
    {
        if (isset($this->experiments[$experimentId])) {
            $this->experiments[$experimentId]['lifecycle_stage'] = 'deleted';
        }
    }

    /**
     * Restore a deleted experiment
     */
    public function restore(string $experimentId): void
    {
        if (isset($this->experiments[$experimentId])) {
            $this->experiments[$experimentId]['lifecycle_stage'] = 'active';
        }
    }

    /**
     * Update an experiment
     */
    public function update(string $experimentId, ?string $newName = null): void
    {
        if (! isset($this->experiments[$experimentId])) {
            throw new \MLflow\Exception\NotFoundException("Experiment {$experimentId} not found");
        }

        if ($newName !== null) {
            $this->experiments[$experimentId]['name'] = $newName;
        }
    }

    /**
     * Delete a tag from an experiment
     */
    public function deleteTag(string $experimentId, string $key): void
    {
        if (isset($this->experiments[$experimentId]['tags']) && is_array($this->experiments[$experimentId]['tags'])) {
            /** @var array<string, string> $tags */
            $tags = $this->experiments[$experimentId]['tags'];
            unset($tags[$key]);
            $this->experiments[$experimentId]['tags'] = $tags;
        }
    }
}
