<?php

declare(strict_types=1);

namespace MLflow\Api;

/**
 * API for managing MLflow models
 */
class ModelApi extends BaseApi
{
    /**
     * Create a registered model
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
     */
    public function getRegisteredModel(string $name): array
    {
        return $this->get('mlflow/registered-models/get', ['name' => $name]);
    }

    /**
     * Update a registered model
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
     */
    public function deleteRegisteredModel(string $name): void
    {
        $this->delete('mlflow/registered-models/delete', ['name' => $name]);
    }

    /**
     * List registered models
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

    /**
     * Format tags for API request
     */
    private function formatTags(array $tags): array
    {
        $formatted = [];
        foreach ($tags as $key => $value) {
            $formatted[] = [
                'key' => (string) $key,
                'value' => (string) $value,
            ];
        }
        return $formatted;
    }
}