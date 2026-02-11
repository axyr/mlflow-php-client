<?php

declare(strict_types=1);

namespace MLflow\Api;

/**
 * API for managing MLflow models
 *
 * @deprecated Use ModelRegistryApi instead. ModelApi will be removed in v3.0.
 *             ModelRegistryApi provides the same functionality with proper return types.
 */
class ModelApi extends BaseApi
{
    /**
     * Create a registered model
     *
     * @param string $name Model name
     * @param string|null $description Model description
     * @param array<string, string> $tags Model tags
     * @return array<string, mixed> Response data
     * @deprecated Use ModelRegistryApi::createRegisteredModel() instead
     */
    public function createRegisteredModel(string $name, ?string $description = null, array $tags = []): array
    {
        $data = ['name' => $name];

        if ($description !== null) {
            $data['description'] = $description;
        }

        if (!empty($tags)) {
            $data['tags'] = $this->formatTags($tags);
        }

        return $this->post('mlflow/registered-models/create', $data);
    }

    /**
     * Get a registered model
     *
     * @param string $name Model name
     * @return array<string, mixed> Response data
     * @deprecated Use ModelRegistryApi::getRegisteredModel() instead
     */
    public function getRegisteredModel(string $name): array
    {
        return $this->get('mlflow/registered-models/get', ['name' => $name]);
    }

    /**
     * Update a registered model
     *
     * @deprecated Use ModelRegistryApi::updateRegisteredModel() instead
     */
    public function updateRegisteredModel(string $name, ?string $description = null): void
    {
        $data = ['name' => $name];

        if ($description !== null) {
            $data['description'] = $description;
        }

        $this->patch('mlflow/registered-models/update', $data);
    }

    /**
     * Delete a registered model
     *
     * @deprecated Use ModelRegistryApi::deleteRegisteredModel() instead
     */
    public function deleteRegisteredModel(string $name): void
    {
        $this->delete('mlflow/registered-models/delete', ['name' => $name]);
    }

    /**
     * List registered models
     *
     * @param int|null $maxResults Maximum number of results
     * @param string|null $pageToken Pagination token
     * @return array<string, mixed> Response data
     * @deprecated Use ModelRegistryApi::searchRegisteredModels() instead
     */
    public function listRegisteredModels(?int $maxResults = null, ?string $pageToken = null): array
    {
        $query = [];

        if ($maxResults !== null) {
            $query['max_results'] = $maxResults;
        }

        if ($pageToken !== null) {
            $query['page_token'] = $pageToken;
        }

        return $this->get('mlflow/registered-models/list', $query);
    }

    /**
     * Create a model version
     *
     * @param string $name Model name
     * @param string $source Model source path
     * @param string|null $runId Run ID
     * @return array<string, mixed> Response data
     * @deprecated Use ModelRegistryApi::createModelVersion() instead
     */
    public function createModelVersion(string $name, string $source, ?string $runId = null): array
    {
        $data = [
            'name' => $name,
            'source' => $source,
        ];

        if ($runId !== null) {
            $data['run_id'] = $runId;
        }

        return $this->post('mlflow/model-versions/create', $data);
    }

    /**
     * Get a model version
     *
     * @param string $name Model name
     * @param string $version Model version
     * @return array<string, mixed> Response data
     * @deprecated Use ModelRegistryApi::getModelVersion() instead
     */
    public function getModelVersion(string $name, string $version): array
    {
        return $this->get('mlflow/model-versions/get', [
            'name' => $name,
            'version' => $version,
        ]);
    }

    /**
     * Transition model version stage
     *
     * @deprecated Use ModelRegistryApi::transitionModelVersionStage() instead
     */
    public function transitionModelVersionStage(
        string $name,
        string $version,
        string $stage,
        bool $archiveExistingVersions = false
    ): void {
        $this->post('mlflow/model-versions/transition-stage', [
            'name' => $name,
            'version' => $version,
            'stage' => $stage,
            'archive_existing_versions' => $archiveExistingVersions,
        ]);
    }

}