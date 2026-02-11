<?php

declare(strict_types=1);

namespace MLflow\Builder;

use MLflow\Api\ModelRegistryApi;
use MLflow\Model\RegisteredModel;

/**
 * Fluent builder for creating registered models in MLflow Model Registry
 *
 * @example
 * ```php
 * $model = $client->createModelBuilder('my-model')
 *     ->withDescription('Image classification model')
 *     ->withTag('framework', 'pytorch')
 *     ->withTag('task', 'classification')
 *     ->create();
 * ```
 */
final class ModelBuilder
{
    private ?string $description = null;
    /** @var array<string, string> */
    private array $tags = [];

    public function __construct(
        private readonly ModelRegistryApi $registryApi,
        private readonly string $name,
    ) {}

    /**
     * Set the model description
     */
    public function withDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Add a single tag
     */
    public function withTag(string $key, string $value): self
    {
        $this->tags[$key] = $value;

        return $this;
    }

    /**
     * Add multiple tags
     *
     * @param array<string, string> $tags
     */
    public function withTags(array $tags): self
    {
        $this->tags = array_merge($this->tags, $tags);

        return $this;
    }

    /**
     * Create the registered model with all configured parameters
     *
     * @return RegisteredModel The created model
     */
    public function create(): RegisteredModel
    {
        return $this->registryApi->createRegisteredModel(
            name: $this->name,
            description: $this->description,
            tags: $this->tags,
        );
    }
}
