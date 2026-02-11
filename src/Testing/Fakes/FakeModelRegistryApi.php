<?php

declare(strict_types=1);

namespace MLflow\Testing\Fakes;

use MLflow\Model\RegisteredModel;

/**
 * Fake implementation of ModelRegistryApi for testing
 */
class FakeModelRegistryApi
{
    /** @var array<string, array<string, mixed>> */
    private array $models = [];

    public function __construct(
        private readonly MLflowFake $fake
    ) {}

    /**
     * Create a new registered model
     *
     * @param string                $name        Model name
     * @param string|null           $description Description
     * @param array<string, string> $tags        Tags
     */
    public function createRegisteredModel(
        string $name,
        ?string $description = null,
        array $tags = []
    ): RegisteredModel {
        $modelData = [
            'name' => $name,
            'description' => $description,
            'tags' => $tags,
            'creation_timestamp' => (int) (microtime(true) * 1000),
            'last_updated_timestamp' => (int) (microtime(true) * 1000),
        ];

        $this->models[$name] = $modelData;

        // Record in fake
        $this->fake->recordModel($modelData);

        return RegisteredModel::fromArray($modelData);
    }

    /**
     * Get a registered model by name
     */
    public function getRegisteredModel(string $name): RegisteredModel
    {
        $data = $this->models[$name] ?? null;

        if ($data === null) {
            throw new \MLflow\Exception\NotFoundException("Model {$name} not found");
        }

        return RegisteredModel::fromArray($data);
    }

    /**
     * Update a registered model
     */
    public function updateRegisteredModel(string $name, ?string $description = null): RegisteredModel
    {
        if (! isset($this->models[$name])) {
            throw new \MLflow\Exception\NotFoundException("Model {$name} not found");
        }

        if ($description !== null) {
            $this->models[$name]['description'] = $description;
        }

        $this->models[$name]['last_updated_timestamp'] = (int) (microtime(true) * 1000);

        return RegisteredModel::fromArray($this->models[$name]);
    }

    /**
     * Delete a registered model
     */
    public function deleteRegisteredModel(string $name): void
    {
        unset($this->models[$name]);
    }

    /**
     * Rename a registered model
     */
    public function renameRegisteredModel(string $name, string $newName): RegisteredModel
    {
        if (! isset($this->models[$name])) {
            throw new \MLflow\Exception\NotFoundException("Model {$name} not found");
        }

        $this->models[$newName] = $this->models[$name];
        $this->models[$newName]['name'] = $newName;
        $this->models[$newName]['last_updated_timestamp'] = (int) (microtime(true) * 1000);
        unset($this->models[$name]);

        return RegisteredModel::fromArray($this->models[$newName]);
    }

    /**
     * Set a tag on a registered model
     */
    public function setRegisteredModelTag(string $name, string $key, string $value): void
    {
        if (! isset($this->models[$name])) {
            throw new \MLflow\Exception\NotFoundException("Model {$name} not found");
        }

        if (! isset($this->models[$name]['tags']) || ! is_array($this->models[$name]['tags'])) {
            $this->models[$name]['tags'] = [];
        }

        /** @var array<string, string> $tags */
        $tags = $this->models[$name]['tags'];
        $tags[$key] = $value;
        $this->models[$name]['tags'] = $tags;
    }

    /**
     * Delete a tag from a registered model
     */
    public function deleteRegisteredModelTag(string $name, string $key): void
    {
        if (isset($this->models[$name]['tags']) && is_array($this->models[$name]['tags'])) {
            /** @var array<string, string> $tags */
            $tags = $this->models[$name]['tags'];
            unset($tags[$key]);
            $this->models[$name]['tags'] = $tags;
        }
    }

    /**
     * Search registered models
     *
     * @param string|null        $filter     Filter string
     * @param int|null           $maxResults Max results
     * @param array<string>|null $orderBy    Order by
     * @param string|null        $pageToken  Page token
     *
     * @return array{registered_models: array<RegisteredModel>, next_page_token: string|null}
     */
    public function searchRegisteredModels(
        ?string $filter = null,
        ?int $maxResults = null,
        ?array $orderBy = null,
        ?string $pageToken = null
    ): array {
        $models = [];

        foreach ($this->models as $data) {
            $models[] = RegisteredModel::fromArray($data);
        }

        return [
            'registered_models' => $models,
            'next_page_token' => null,
        ];
    }
}
