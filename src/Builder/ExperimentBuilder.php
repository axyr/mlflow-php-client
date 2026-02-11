<?php

declare(strict_types=1);

namespace MLflow\Builder;

use MLflow\Api\ExperimentApi;
use MLflow\Model\Experiment;

/**
 * Fluent builder for creating MLflow experiments
 *
 * @example
 * ```php
 * $experiment = $client->createExperimentBuilder('my-experiment')
 *     ->withArtifactLocation('s3://bucket/path')
 *     ->withTag('team', 'ml-team')
 *     ->withTag('project', 'recommendation')
 *     ->create();
 * ```
 */
final class ExperimentBuilder
{
    private ?string $artifactLocation = null;
    /** @var array<string, string> */
    private array $tags = [];

    public function __construct(
        private readonly ExperimentApi $experimentApi,
        private readonly string $name,
    ) {}

    /**
     * Set the artifact location for the experiment
     */
    public function withArtifactLocation(string $location): self
    {
        $this->artifactLocation = $location;

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
     * Create the experiment with all configured parameters
     *
     * @return Experiment The created experiment
     */
    public function create(): Experiment
    {
        return $this->experimentApi->create(
            name: $this->name,
            artifactLocation: $this->artifactLocation,
            tags: $this->tags,
        );
    }
}
