<?php

declare(strict_types=1);

namespace MLflow\Contracts;

use MLflow\Builder\ExperimentBuilder;
use MLflow\Builder\ModelBuilder;
use MLflow\Builder\RunBuilder;
use MLflow\Builder\TraceBuilder;

/**
 * Contract for MLflow Client
 */
interface MLflowClientContract
{
    /**
     * Get the Experiment API instance
     */
    public function experiments(): ExperimentApiContract;

    /**
     * Get the Run API instance
     */
    public function runs(): RunApiContract;

    /**
     * Get the Model Registry API instance
     */
    public function modelRegistry(): ModelRegistryApiContract;

    /**
     * Get the Model Registry API instance (alias for modelRegistry())
     */
    public function models(): ModelRegistryApiContract;

    /**
     * Get the Metric API instance
     */
    public function metrics(): MetricApiContract;

    /**
     * Get the Artifact API instance
     */
    public function artifacts(): ArtifactApiContract;

    /**
     * Get the tracking URI
     */
    public function getTrackingUri(): string;

    /**
     * Create a run builder for fluent API
     */
    public function createRunBuilder(string $experimentId): RunBuilder;

    /**
     * Create an experiment builder for fluent API
     */
    public function createExperimentBuilder(string $name): ExperimentBuilder;

    /**
     * Create a model builder for fluent API
     */
    public function createModelBuilder(string $name): ModelBuilder;

    /**
     * Create a trace builder for fluent API
     */
    public function createTraceBuilder(string $experimentId, string $name): TraceBuilder;

    /**
     * Validate connection to MLflow server
     *
     * @return bool True if connection is successful
     */
    public function validateConnection(): bool;

    /**
     * Get MLflow server information
     *
     * @return array{version: string|null, reachable: bool, error: string|null}
     */
    public function getServerInfo(): array;
}
