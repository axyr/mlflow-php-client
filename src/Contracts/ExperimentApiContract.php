<?php

declare(strict_types=1);

namespace MLflow\Contracts;

use MLflow\Enum\ViewType;
use MLflow\Model\Experiment;

/**
 * Contract for Experiment API
 */
interface ExperimentApiContract
{
    /**
     * Create a new experiment
     *
     * @param string                $name             Experiment name (must be unique)
     * @param string|null           $artifactLocation Optional artifact location
     * @param array<string, string> $tags             Optional tags for the experiment
     *
     * @return Experiment The created experiment
     */
    public function create(string $name, ?string $artifactLocation = null, array $tags = []): Experiment;

    /**
     * Get an experiment by ID
     *
     * @param string $experimentId The experiment ID
     *
     * @return Experiment The experiment
     */
    public function getById(string $experimentId): Experiment;

    /**
     * Get an experiment by name
     *
     * @param string $name The experiment name
     *
     * @return Experiment The experiment
     */
    public function getByName(string $name): Experiment;

    /**
     * Search experiments
     *
     * @param string|null        $filterString Filter string
     * @param int|null           $maxResults   Maximum number of experiments to return
     * @param string|null        $pageToken    Token for pagination
     * @param array<string>|null $orderBy      List of columns to order by
     * @param ViewType           $viewType     View type for filtering by lifecycle stage
     *
     * @return array{experiments: array<Experiment>, next_page_token: string|null}
     */
    public function search(
        ?string $filterString = null,
        ?int $maxResults = null,
        ?string $pageToken = null,
        ?array $orderBy = null,
        ViewType $viewType = ViewType::ACTIVE_ONLY
    ): array;

    /**
     * Update an experiment
     *
     * @param string      $experimentId The experiment ID
     * @param string|null $newName      New name for the experiment
     */
    public function update(string $experimentId, ?string $newName = null): void;

    /**
     * Delete an experiment
     *
     * @param string $experimentId The experiment ID
     */
    public function deleteExperiment(string $experimentId): void;

    /**
     * Restore a deleted experiment
     *
     * @param string $experimentId The experiment ID
     */
    public function restore(string $experimentId): void;

    /**
     * Set a tag on an experiment
     *
     * @param string $experimentId The experiment ID
     * @param string $key          Tag key
     * @param string $value        Tag value
     */
    public function setTag(string $experimentId, string $key, string $value): void;

    /**
     * Delete a tag from an experiment
     *
     * @param string $experimentId The experiment ID
     * @param string $key          Tag key to delete
     */
    public function deleteTag(string $experimentId, string $key): void;
}
