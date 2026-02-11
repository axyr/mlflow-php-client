<?php

declare(strict_types=1);

namespace MLflow\Builder;

use MLflow\Api\RunApi;
use MLflow\Model\Run;

/**
 * Fluent builder for creating and configuring MLflow runs
 *
 * @example
 * ```php
 * $run = $client->createRunBuilder($experimentId)
 *     ->withName('training-001')
 *     ->withParam('learning_rate', '0.01')
 *     ->withParam('batch_size', '32')
 *     ->withMetric('accuracy', 0.95)
 *     ->withTag('model_type', 'neural_network')
 *     ->start();
 * ```
 */
final class RunBuilder
{
    private ?string $runName = null;
    private ?int $startTime = null;
    private ?string $userId = null;
    /** @var array<string, string> */
    private array $tags = [];
    /** @var array<string, string> */
    private array $params = [];
    /** @var array<array{key: string, value: float, step: int, timestamp: int}> */
    private array $metrics = [];

    public function __construct(
        private readonly RunApi $runApi,
        private readonly string $experimentId,
    ) {}

    /**
     * Set the run name
     */
    public function withName(string $name): self
    {
        $this->runName = $name;

        return $this;
    }

    /**
     * Set the start time (milliseconds since epoch)
     */
    public function withStartTime(int $timestamp): self
    {
        $this->startTime = $timestamp;

        return $this;
    }

    /**
     * Set the user ID
     */
    public function withUserId(string $userId): self
    {
        $this->userId = $userId;

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
     * Add a single parameter
     */
    public function withParam(string $key, string $value): self
    {
        $this->params[$key] = $value;

        return $this;
    }

    /**
     * Add multiple parameters
     *
     * @param array<string, string> $params
     */
    public function withParams(array $params): self
    {
        $this->params = array_merge($this->params, $params);

        return $this;
    }

    /**
     * Add a single metric
     */
    public function withMetric(string $key, float $value, ?int $step = null, ?int $timestamp = null): self
    {
        $this->metrics[] = [
            'key' => $key,
            'value' => $value,
            'step' => $step ?? 0,
            'timestamp' => $timestamp ?? (int) (microtime(true) * 1000),
        ];

        return $this;
    }

    /**
     * Add multiple metrics
     *
     * @param array<array{key: string, value: float, step?: int, timestamp?: int}> $metrics
     */
    public function withMetrics(array $metrics): self
    {
        foreach ($metrics as $metric) {
            $this->withMetric(
                $metric['key'],
                $metric['value'],
                $metric['step'] ?? null,
                $metric['timestamp'] ?? null
            );
        }

        return $this;
    }

    /**
     * Create and start the run with all configured parameters
     *
     * @return Run The created run
     */
    public function start(): Run
    {
        // Create the run
        $run = $this->runApi->create(
            experimentId: $this->experimentId,
            userId: $this->userId,
            runName: $this->runName,
            startTime: $this->startTime ?? (int) (microtime(true) * 1000),
            tags: $this->tags,
        );

        // Log batch data if any params or metrics were configured
        if (!empty($this->params) || !empty($this->metrics)) {
            $this->runApi->logBatch(
                runId: $run->info->runId,
                metrics: $this->metrics,
                params: $this->params,
            );

            // Refresh run to get the logged data
            $run = $this->runApi->getById($run->info->runId);
        }

        return $run;
    }

    /**
     * Create the run without starting it (for manual control)
     *
     * @return Run The created run
     */
    public function create(): Run
    {
        return $this->runApi->create(
            experimentId: $this->experimentId,
            userId: $this->userId,
            runName: $this->runName,
            startTime: $this->startTime ?? (int) (microtime(true) * 1000),
            tags: $this->tags,
        );
    }
}
