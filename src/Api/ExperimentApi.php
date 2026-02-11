<?php

declare(strict_types=1);

namespace MLflow\Api;

use MLflow\Enum\ViewType;
use MLflow\Model\Experiment;
use MLflow\Model\ExperimentTag;
use MLflow\Exception\MLflowException;

/**
 * Complete API for managing MLflow experiments
 * Implements all REST API endpoints from MLflow official documentation
 */
class ExperimentApi extends BaseApi
{
    /**
     * Create a new experiment
     *
     * @param string $name Experiment name (must be unique)
     * @param string|null $artifactLocation Optional artifact location
     * @param array<string, string> $tags Optional tags for the experiment
     * @return Experiment The created experiment
     * @throws MLflowException
     */
    public function create(string $name, ?string $artifactLocation = null, array $tags = []): Experiment
    {
        $data = ['name' => $name];

        if ($artifactLocation !== null) {
            $data['artifact_location'] = $artifactLocation;
        }

        if (!empty($tags)) {
            $data['tags'] = $this->formatTags($tags);
        }

        $response = $this->post('mlflow/experiments/create', $data);

        $experimentId = $response['experiment_id'] ?? '';
        if (!is_string($experimentId)) {
            throw new MLflowException('Invalid experiment_id in response');
        }

        return new Experiment($experimentId, $name);
    }

    /**
     * Get an experiment by ID
     *
     * @param string $experimentId The experiment ID
     * @return Experiment The experiment
     * @throws MLflowException
     */
    public function getById(string $experimentId): Experiment
    {
        $response = $this->get('mlflow/experiments/get', [
            'experiment_id' => $experimentId,
        ]);

        $experiment = $response['experiment'] ?? null;
        if (!is_array($experiment)) {
            throw new MLflowException('Invalid experiment data in response');
        }

        return Experiment::fromArray($experiment);
    }

    /**
     * Get an experiment by name
     *
     * @param string $name The experiment name
     * @return Experiment The experiment
     * @throws MLflowException
     */
    public function getByName(string $name): Experiment
    {
        $response = $this->get('mlflow/experiments/get-by-name', [
            'experiment_name' => $name,
        ]);

        $experiment = $response['experiment'] ?? null;
        if (!is_array($experiment)) {
            throw new MLflowException('Invalid experiment data in response');
        }

        return Experiment::fromArray($experiment);
    }

    /**
     * Search experiments (preferred over list)
     *
     * @param string|null $filterString Filter string (e.g., "attribute.name = 'my_experiment'")
     * @param int|null $maxResults Maximum number of experiments to return
     * @param string|null $pageToken Token for pagination
     * @param array<string>|null $orderBy List of columns to order by (e.g., ["name DESC", "creation_time"])
     * @param ViewType $viewType View type for filtering by lifecycle stage
     * @return array{experiments: array<Experiment>, next_page_token: string|null} Array of experiments and pagination info
     * @throws MLflowException
     */
    public function search(
        ?string $filterString = null,
        ?int $maxResults = null,
        ?string $pageToken = null,
        ?array $orderBy = null,
        ViewType $viewType = ViewType::ACTIVE_ONLY
    ): array {
        $data = ['view_type' => $viewType->value];

        if ($filterString !== null) {
            $data['filter'] = $filterString;
        }

        if ($maxResults !== null) {
            $data['max_results'] = $maxResults;
        }

        if ($pageToken !== null) {
            $data['page_token'] = $pageToken;
        }

        if ($orderBy !== null) {
            $data['order_by'] = $orderBy;
        }

        $response = $this->post('mlflow/experiments/search', $data);

        $experiments = [];
        if (isset($response['experiments']) && is_array($response['experiments'])) {
            foreach ($response['experiments'] as $expData) {
                if (is_array($expData)) {
                    $experiments[] = Experiment::fromArray($expData);
                }
            }
        }

        $nextPageToken = $response['next_page_token'] ?? null;

        return [
            'experiments' => $experiments,
            'next_page_token' => is_string($nextPageToken) ? $nextPageToken : null,
        ];
    }

    /**
     * List all experiments (deprecated - use search instead)
     *
     * @deprecated Use search() method instead
     * @param ViewType $viewType View type for filtering by lifecycle stage
     * @param int|null $maxResults Maximum number of experiments to return
     * @param string|null $pageToken Token for pagination
     * @return array{experiments: array<Experiment>, next_page_token: string|null} Array of experiments and pagination info
     * @throws MLflowException
     */
    public function list(
        ViewType $viewType = ViewType::ACTIVE_ONLY,
        ?int $maxResults = null,
        ?string $pageToken = null
    ): array {
        return $this->search(null, $maxResults, $pageToken, null, $viewType);
    }

    /**
     * Update an experiment
     *
     * @param string $experimentId The experiment ID
     * @param string|null $newName New name for the experiment
     * @return void
     * @throws MLflowException
     */
    public function update(string $experimentId, ?string $newName = null): void
    {
        $data = ['experiment_id' => $experimentId];

        if ($newName !== null) {
            $data['new_name'] = $newName;
        }

        $this->post('mlflow/experiments/update', $data);
    }

    /**
     * Delete an experiment
     *
     * @param string $experimentId The experiment ID
     * @return void
     * @throws MLflowException
     */
    public function deleteExperiment(string $experimentId): void
    {
        $this->post('mlflow/experiments/delete', [
            'experiment_id' => $experimentId,
        ]);
    }

    /**
     * Restore a deleted experiment
     *
     * @param string $experimentId The experiment ID
     * @return void
     * @throws MLflowException
     */
    public function restore(string $experimentId): void
    {
        $this->post('mlflow/experiments/restore', [
            'experiment_id' => $experimentId,
        ]);
    }

    /**
     * Set a tag on an experiment
     *
     * @param string $experimentId The experiment ID
     * @param string $key Tag key
     * @param string $value Tag value
     * @return void
     * @throws MLflowException
     */
    public function setTag(string $experimentId, string $key, string $value): void
    {
        $this->post('mlflow/experiments/set-experiment-tag', [
            'experiment_id' => $experimentId,
            'key' => $key,
            'value' => $value,
        ]);
    }

    /**
     * Delete a tag from an experiment
     *
     * @param string $experimentId The experiment ID
     * @param string $key Tag key to delete
     * @return void
     * @throws MLflowException
     */
    public function deleteTag(string $experimentId, string $key): void
    {
        $this->post('mlflow/experiments/delete-experiment-tag', [
            'experiment_id' => $experimentId,
            'key' => $key,
        ]);
    }

}