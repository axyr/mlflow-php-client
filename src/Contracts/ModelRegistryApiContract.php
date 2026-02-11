<?php

declare(strict_types=1);

namespace MLflow\Contracts;

use MLflow\Model\RegisteredModel;

/**
 * Contract for Model Registry API
 */
interface ModelRegistryApiContract
{
    /**
     * Create a new registered model
     *
     * @param string                $name        The model name (must be unique)
     * @param string|null           $description Optional description
     * @param array<string, string> $tags        Optional tags
     *
     * @return RegisteredModel The created model
     */
    public function createRegisteredModel(
        string $name,
        ?string $description = null,
        array $tags = []
    ): RegisteredModel;

    /**
     * Get a registered model by name
     *
     * @param string $name The model name
     *
     * @return RegisteredModel The model
     */
    public function getRegisteredModel(string $name): RegisteredModel;

    /**
     * Update a registered model
     *
     * @param string      $name        The model name
     * @param string|null $description New description
     *
     * @return RegisteredModel The updated model
     */
    public function updateRegisteredModel(string $name, ?string $description = null): RegisteredModel;

    /**
     * Delete a registered model
     *
     * @param string $name The model name
     */
    public function deleteRegisteredModel(string $name): void;

    /**
     * Rename a registered model
     *
     * @param string $name    Current model name
     * @param string $newName New model name
     *
     * @return RegisteredModel The renamed model
     */
    public function renameRegisteredModel(string $name, string $newName): RegisteredModel;

    /**
     * Set a tag on a registered model
     *
     * @param string $name  The model name
     * @param string $key   Tag key
     * @param string $value Tag value
     */
    public function setRegisteredModelTag(string $name, string $key, string $value): void;

    /**
     * Delete a tag from a registered model
     *
     * @param string $name The model name
     * @param string $key  Tag key to delete
     */
    public function deleteRegisteredModelTag(string $name, string $key): void;

    /**
     * Search registered models
     *
     * @param string|null        $filter     Filter string
     * @param int|null           $maxResults Maximum number of models to return
     * @param array<string>|null $orderBy    List of columns to order by
     * @param string|null        $pageToken  Pagination token
     *
     * @return array{registered_models: array<RegisteredModel>, next_page_token: string|null}
     */
    public function searchRegisteredModels(
        ?string $filter = null,
        ?int $maxResults = null,
        ?array $orderBy = null,
        ?string $pageToken = null
    ): array;
}
