<?php

declare(strict_types=1);

namespace MLflow\Api;

use MLflow\Enum\ModelStage;
use MLflow\Model\RegisteredModel;
use MLflow\Model\ModelVersion;
use MLflow\Exception\MLflowException;

/**
 * Complete API for MLflow Model Registry
 * Implements all REST API endpoints from MLflow official documentation
 */
class ModelRegistryApi extends BaseApi
{
    // ========== REGISTERED MODEL ENDPOINTS ==========

    /**
     * Create a new registered model
     *
     * @param string $name The model name (must be unique)
     * @param string|null $description Optional description
     * @param array<string, string> $tags Optional tags
     * @return RegisteredModel The created model
     * @throws MLflowException
     */
    public function createRegisteredModel(
        string $name,
        ?string $description = null,
        array $tags = []
    ): RegisteredModel {
        $data = ['name' => $name];

        if ($description !== null) {
            $data['description'] = $description;
        }

        if (!empty($tags)) {
            $data['tags'] = $this->formatTags($tags);
        }

        $response = $this->post('mlflow/registered-models/create', $data);

        $model = $response['registered_model'] ?? null;
        if (!is_array($model)) {
            throw new MLflowException('Invalid registered_model data in response');
        }

        return RegisteredModel::fromArray($model);
    }

    /**
     * Get a registered model by name
     *
     * @param string $name The model name
     * @return RegisteredModel The model
     * @throws MLflowException
     */
    public function getRegisteredModel(string $name): RegisteredModel
    {
        $response = $this->get('mlflow/registered-models/get', ['name' => $name]);

        $model = $response['registered_model'] ?? null;
        if (!is_array($model)) {
            throw new MLflowException('Invalid registered_model data in response');
        }

        return RegisteredModel::fromArray($model);
    }

    /**
     * Update a registered model
     *
     * @param string $name The model name
     * @param string|null $description New description
     * @return RegisteredModel The updated model
     * @throws MLflowException
     */
    public function updateRegisteredModel(string $name, ?string $description = null): RegisteredModel
    {
        $data = ['name' => $name];

        if ($description !== null) {
            $data['description'] = $description;
        }

        $response = $this->patch('mlflow/registered-models/update', $data);

        $model = $response['registered_model'] ?? null;
        if (!is_array($model)) {
            throw new MLflowException('Invalid registered_model data in response');
        }

        return RegisteredModel::fromArray($model);
    }

    /**
     * Rename a registered model
     *
     * @param string $name Current model name
     * @param string $newName New model name
     * @return RegisteredModel The renamed model
     * @throws MLflowException
     */
    public function renameRegisteredModel(string $name, string $newName): RegisteredModel
    {
        $response = $this->post('mlflow/registered-models/rename', [
            'name' => $name,
            'new_name' => $newName,
        ]);

        $model = $response['registered_model'] ?? null;
        if (!is_array($model)) {
            throw new MLflowException('Invalid registered_model data in response');
        }

        return RegisteredModel::fromArray($model);
    }

    /**
     * Delete a registered model
     *
     * @param string $name The model name
     * @return void
     * @throws MLflowException
     */
    public function deleteRegisteredModel(string $name): void
    {
        $this->delete('mlflow/registered-models/delete', ['name' => $name]);
    }

    /**
     * Search for registered models
     *
     * @param string|null $filter Filter string (e.g., "name = 'my_model'")
     * @param int|null $maxResults Maximum number of models to return
     * @param array<string>|null $orderBy List of columns to order by
     * @param string|null $pageToken Pagination token
     * @return array{registered_models: array<RegisteredModel>, next_page_token: string|null} Array with models and next_page_token
     * @throws MLflowException
     */
    public function searchRegisteredModels(
        ?string $filter = null,
        ?int $maxResults = null,
        ?array $orderBy = null,
        ?string $pageToken = null
    ): array {
        $data = [];

        if ($filter !== null) {
            $data['filter'] = $filter;
        }

        if ($maxResults !== null) {
            $data['max_results'] = $maxResults;
        }

        if ($orderBy !== null) {
            $data['order_by'] = $orderBy;
        }

        if ($pageToken !== null) {
            $data['page_token'] = $pageToken;
        }

        $response = $this->post('mlflow/registered-models/search', $data);

        $models = [];
        if (isset($response['registered_models']) && is_array($response['registered_models'])) {
            foreach ($response['registered_models'] as $modelData) {
                if (is_array($modelData)) {
                    $models[] = RegisteredModel::fromArray($modelData);
                }
            }
        }

        $nextPageToken = $response['next_page_token'] ?? null;

        return [
            'registered_models' => $models,
            'next_page_token' => is_string($nextPageToken) ? $nextPageToken : null,
        ];
    }

    /**
     * Get latest model versions for stages
     *
     * @param string $name The model name
     * @param array<ModelStage>|null $stages List of stages to retrieve versions for
     * @return array<ModelVersion> Array of ModelVersion objects
     * @throws MLflowException
     */
    public function getLatestVersions(string $name, ?array $stages = null): array
    {
        $data = ['name' => $name];

        if ($stages !== null) {
            $data['stages'] = array_map(fn(ModelStage $stage) => $stage->value, $stages);
        }

        $response = $this->post('mlflow/registered-models/get-latest-versions', $data);

        $versions = [];
        if (isset($response['model_versions']) && is_array($response['model_versions'])) {
            foreach ($response['model_versions'] as $versionData) {
                if (is_array($versionData)) {
                    $versions[] = ModelVersion::fromArray($versionData);
                }
            }
        }

        return $versions;
    }

    /**
     * Set a tag on a registered model
     *
     * @param string $name The model name
     * @param string $key Tag key
     * @param string $value Tag value
     * @return void
     * @throws MLflowException
     */
    public function setRegisteredModelTag(string $name, string $key, string $value): void
    {
        $this->post('mlflow/registered-models/set-tag', [
            'name' => $name,
            'key' => $key,
            'value' => $value,
        ]);
    }

    /**
     * Delete a tag from a registered model
     *
     * @param string $name The model name
     * @param string $key Tag key to delete
     * @return void
     * @throws MLflowException
     */
    public function deleteRegisteredModelTag(string $name, string $key): void
    {
        $this->post('mlflow/registered-models/delete-tag', [
            'name' => $name,
            'key' => $key,
        ]);
    }

    /**
     * Set an alias for a model version
     *
     * @param string $name The model name
     * @param string $alias The alias to set
     * @param string $version The version number
     * @return void
     * @throws MLflowException
     */
    public function setRegisteredModelAlias(string $name, string $alias, string $version): void
    {
        $this->post('mlflow/registered-models/set-alias', [
            'name' => $name,
            'alias' => $alias,
            'version' => $version,
        ]);
    }

    /**
     * Delete an alias from a registered model
     *
     * @param string $name The model name
     * @param string $alias The alias to delete
     * @return void
     * @throws MLflowException
     */
    public function deleteRegisteredModelAlias(string $name, string $alias): void
    {
        $this->post('mlflow/registered-models/delete-alias', [
            'name' => $name,
            'alias' => $alias,
        ]);
    }

    /**
     * Get a model version by alias
     *
     * @param string $name The model name
     * @param string $alias The alias
     * @return ModelVersion The model version
     * @throws MLflowException
     */
    public function getModelVersionByAlias(string $name, string $alias): ModelVersion
    {
        $response = $this->get('mlflow/registered-models/get-version-by-alias', [
            'name' => $name,
            'alias' => $alias,
        ]);

        $version = $response['model_version'] ?? null;
        if (!is_array($version)) {
            throw new MLflowException('Invalid model_version data in response');
        }

        return ModelVersion::fromArray($version);
    }

    // ========== MODEL VERSION ENDPOINTS ==========

    /**
     * Create a new model version
     *
     * @param string $name The model name
     * @param string $source The source path where the model artifacts are stored
     * @param string|null $runId Optional run ID that generated this model
     * @param string|null $description Optional description
     * @param array<string, string> $tags Optional tags
     * @param string|null $runLink Optional link to the run
     * @return ModelVersion The created model version
     * @throws MLflowException
     */
    public function createModelVersion(
        string $name,
        string $source,
        ?string $runId = null,
        ?string $description = null,
        array $tags = [],
        ?string $runLink = null
    ): ModelVersion {
        $data = [
            'name' => $name,
            'source' => $source,
        ];

        if ($runId !== null) {
            $data['run_id'] = $runId;
        }

        if ($description !== null) {
            $data['description'] = $description;
        }

        if (!empty($tags)) {
            $data['tags'] = $this->formatTags($tags);
        }

        if ($runLink !== null) {
            $data['run_link'] = $runLink;
        }

        $response = $this->post('mlflow/model-versions/create', $data);

        $version = $response['model_version'] ?? null;
        if (!is_array($version)) {
            throw new MLflowException('Invalid model_version data in response');
        }

        return ModelVersion::fromArray($version);
    }

    /**
     * Get a specific model version
     *
     * @param string $name The model name
     * @param string $version The version number
     * @return ModelVersion The model version
     * @throws MLflowException
     */
    public function getModelVersion(string $name, string $version): ModelVersion
    {
        $response = $this->get('mlflow/model-versions/get', [
            'name' => $name,
            'version' => $version,
        ]);

        $versionData = $response['model_version'] ?? null;
        if (!is_array($versionData)) {
            throw new MLflowException('Invalid model_version data in response');
        }

        return ModelVersion::fromArray($versionData);
    }

    /**
     * Update a model version
     *
     * @param string $name The model name
     * @param string $version The version number
     * @param string|null $description New description
     * @return ModelVersion The updated model version
     * @throws MLflowException
     */
    public function updateModelVersion(
        string $name,
        string $version,
        ?string $description = null
    ): ModelVersion {
        $data = [
            'name' => $name,
            'version' => $version,
        ];

        if ($description !== null) {
            $data['description'] = $description;
        }

        $response = $this->patch('mlflow/model-versions/update', $data);

        $versionData = $response['model_version'] ?? null;
        if (!is_array($versionData)) {
            throw new MLflowException('Invalid model_version data in response');
        }

        return ModelVersion::fromArray($versionData);
    }

    /**
     * Delete a model version
     *
     * @param string $name The model name
     * @param string $version The version number
     * @return void
     * @throws MLflowException
     */
    public function deleteModelVersion(string $name, string $version): void
    {
        $this->delete('mlflow/model-versions/delete', [
            'name' => $name,
            'version' => $version,
        ]);
    }

    /**
     * Search for model versions
     *
     * @param string|null $filter Filter string
     * @param int|null $maxResults Maximum number of versions to return
     * @param array<string>|null $orderBy List of columns to order by
     * @param string|null $pageToken Pagination token
     * @return array{model_versions: array<ModelVersion>, next_page_token: string|null} Array with model_versions and next_page_token
     * @throws MLflowException
     */
    public function searchModelVersions(
        ?string $filter = null,
        ?int $maxResults = null,
        ?array $orderBy = null,
        ?string $pageToken = null
    ): array {
        $data = [];

        if ($filter !== null) {
            $data['filter'] = $filter;
        }

        if ($maxResults !== null) {
            $data['max_results'] = $maxResults;
        }

        if ($orderBy !== null) {
            $data['order_by'] = $orderBy;
        }

        if ($pageToken !== null) {
            $data['page_token'] = $pageToken;
        }

        $response = $this->post('mlflow/model-versions/search', $data);

        $versions = [];
        if (isset($response['model_versions']) && is_array($response['model_versions'])) {
            foreach ($response['model_versions'] as $versionData) {
                if (is_array($versionData)) {
                    $versions[] = ModelVersion::fromArray($versionData);
                }
            }
        }

        $nextPageToken = $response['next_page_token'] ?? null;

        return [
            'model_versions' => $versions,
            'next_page_token' => is_string($nextPageToken) ? $nextPageToken : null,
        ];
    }

    /**
     * Transition a model version to a new stage
     *
     * @param string $name The model name
     * @param string $version The version number
     * @param ModelStage $stage The target stage
     * @param bool $archiveExistingVersions Whether to archive existing versions in the target stage
     * @return ModelVersion The updated model version
     * @throws MLflowException
     */
    public function transitionModelVersionStage(
        string $name,
        string $version,
        ModelStage $stage,
        bool $archiveExistingVersions = false
    ): ModelVersion {
        $response = $this->post('mlflow/model-versions/transition-stage', [
            'name' => $name,
            'version' => $version,
            'stage' => $stage->value,
            'archive_existing_versions' => $archiveExistingVersions,
        ]);

        $versionData = $response['model_version'] ?? null;
        if (!is_array($versionData)) {
            throw new MLflowException('Invalid model_version data in response');
        }

        return ModelVersion::fromArray($versionData);
    }

    /**
     * Get download URI for model version artifacts
     *
     * @param string $name The model name
     * @param string $version The version number
     * @return string The download URI
     * @throws MLflowException
     */
    public function getModelVersionDownloadUri(string $name, string $version): string
    {
        $response = $this->get('mlflow/model-versions/get-download-uri', [
            'name' => $name,
            'version' => $version,
        ]);

        $uri = $response['artifact_uri'] ?? '';
        return is_string($uri) ? $uri : '';
    }

    /**
     * Set a tag on a model version
     *
     * @param string $name The model name
     * @param string $version The version number
     * @param string $key Tag key
     * @param string $value Tag value
     * @return void
     * @throws MLflowException
     */
    public function setModelVersionTag(string $name, string $version, string $key, string $value): void
    {
        $this->post('mlflow/model-versions/set-tag', [
            'name' => $name,
            'version' => $version,
            'key' => $key,
            'value' => $value,
        ]);
    }

    /**
     * Delete a tag from a model version
     *
     * @param string $name The model name
     * @param string $version The version number
     * @param string $key Tag key to delete
     * @return void
     * @throws MLflowException
     */
    public function deleteModelVersionTag(string $name, string $version, string $key): void
    {
        $this->post('mlflow/model-versions/delete-tag', [
            'name' => $name,
            'version' => $version,
            'key' => $key,
        ]);
    }

    /**
     * Copy a model version to another registered model (Unity Catalog migration)
     *
     * @param string $sourceName Source model name
     * @param string $sourceVersion Source version
     * @param string $destinationName Destination model name
     * @return ModelVersion The copied model version
     * @throws MLflowException
     */
    public function copyModelVersion(
        string $sourceName,
        string $sourceVersion,
        string $destinationName
    ): ModelVersion {
        $response = $this->post('mlflow/model-versions/copy', [
            'src_model_name' => $sourceName,
            'src_model_version' => $sourceVersion,
            'dst_model_name' => $destinationName,
        ]);

        $version = $response['model_version'] ?? null;
        if (!is_array($version)) {
            throw new MLflowException('Invalid model_version data in response');
        }

        return ModelVersion::fromArray($version);
    }
}
