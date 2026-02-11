<?php

declare(strict_types=1);

namespace MLflow;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface;
use MLflow\Api\ArtifactApi;
use MLflow\Api\DatasetApi;
use MLflow\Api\ExperimentApi;
use MLflow\Api\MetricApi;
use MLflow\Api\ModelRegistryApi;
use MLflow\Api\PromptApi;
use MLflow\Api\RunApi;
use MLflow\Api\TraceApi;
use MLflow\Api\WebhookApi;
use MLflow\Builder\ExperimentBuilder;
use MLflow\Builder\ModelBuilder;
use MLflow\Builder\RunBuilder;
use MLflow\Builder\TraceBuilder;
use MLflow\Config\MLflowConfig;
use MLflow\Exception\MLflowException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Main MLflow client class for interacting with MLflow Tracking Server
 */
class MLflowClient
{
    private ClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $trackingUri;
    private ?ExperimentApi $experimentApi = null;
    private ?RunApi $runApi = null;
    private ?ModelRegistryApi $modelRegistryApi = null;
    private ?MetricApi $metricApi = null;
    private ?ArtifactApi $artifactApi = null;
    private ?TraceApi $traceApi = null;
    private ?PromptApi $promptApi = null;
    private ?WebhookApi $webhookApi = null;
    private ?DatasetApi $datasetApi = null;

    /**
     * MLflowClient constructor.
     *
     * @param string $trackingUri The MLflow tracking server URI
     * @param MLflowConfig|array<string, mixed> $config Configuration object or array for backward compatibility
     * @param LoggerInterface|null $logger PSR-3 compatible logger
     */
    public function __construct(
        string $trackingUri,
        MLflowConfig|array $config = [],
        ?LoggerInterface $logger = null
    ) {
        $this->trackingUri = rtrim($trackingUri, '/');
        $this->logger = $logger ?? new NullLogger();

        // Convert array to MLflowConfig for backward compatibility
        $mlflowConfig = $config instanceof MLflowConfig
            ? $config
            : MLflowConfig::fromArray($config);

        $defaultConfig = [
            'base_uri' => $this->trackingUri . '/api/2.0/',
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ];

        $guzzleConfig = array_merge($defaultConfig, $mlflowConfig->toGuzzleArray());

        $this->httpClient = new HttpClient($guzzleConfig);

        $this->logger->info('MLflow client initialized', ['tracking_uri' => $this->trackingUri]);
    }

    /**
     * Get the Experiment API instance
     */
    public function experiments(): ExperimentApi
    {
        if ($this->experimentApi === null) {
            $this->experimentApi = new ExperimentApi($this->httpClient, $this->logger);
        }
        return $this->experimentApi;
    }

    /**
     * Get the Run API instance
     */
    public function runs(): RunApi
    {
        if ($this->runApi === null) {
            $this->runApi = new RunApi($this->httpClient, $this->logger);
        }
        return $this->runApi;
    }

    /**
     * Get the Model Registry API instance
     */
    public function modelRegistry(): ModelRegistryApi
    {
        if ($this->modelRegistryApi === null) {
            $this->modelRegistryApi = new ModelRegistryApi($this->httpClient, $this->logger);
        }
        return $this->modelRegistryApi;
    }

    /**
     * Get the Model Registry API instance (alias for modelRegistry())
     */
    public function models(): ModelRegistryApi
    {
        return $this->modelRegistry();
    }

    /**
     * Get the Metric API instance
     */
    public function metrics(): MetricApi
    {
        if ($this->metricApi === null) {
            $this->metricApi = new MetricApi($this->httpClient, $this->logger);
        }
        return $this->metricApi;
    }

    /**
     * Get the Artifact API instance
     */
    public function artifacts(): ArtifactApi
    {
        if ($this->artifactApi === null) {
            $this->artifactApi = new ArtifactApi($this->httpClient, $this->logger);
        }
        return $this->artifactApi;
    }

    /**
     * Get the tracking URI
     */
    public function getTrackingUri(): string
    {
        return $this->trackingUri;
    }

    /**
     * Set a custom HTTP client
     */
    public function setHttpClient(ClientInterface $httpClient): void
    {
        $this->httpClient = $httpClient;
        // Reset API instances to use new client
        $this->experimentApi = null;
        $this->runApi = null;
        $this->modelRegistryApi = null;
        $this->metricApi = null;
        $this->artifactApi = null;
        $this->traceApi = null;
        $this->promptApi = null;
        $this->webhookApi = null;
        $this->datasetApi = null;
    }

    /**
     * Set a logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Get the Trace API instance
     */
    public function traces(): TraceApi
    {
        if ($this->traceApi === null) {
            $this->traceApi = new TraceApi($this->httpClient, $this->logger);
        }
        return $this->traceApi;
    }

    /**
     * Create a trace builder for fluent API
     */
    public function createTraceBuilder(string $experimentId, string $name): TraceBuilder
    {
        return new TraceBuilder($experimentId, $name);
    }

    /**
     * Create a run builder for fluent API
     *
     * @example
     * ```php
     * $run = $client->createRunBuilder($experimentId)
     *     ->withName('training-001')
     *     ->withParam('lr', '0.01')
     *     ->withMetric('accuracy', 0.95)
     *     ->start();
     * ```
     */
    public function createRunBuilder(string $experimentId): RunBuilder
    {
        return new RunBuilder($this->runs(), $experimentId);
    }

    /**
     * Create an experiment builder for fluent API
     *
     * @example
     * ```php
     * $exp = $client->createExperimentBuilder('my-experiment')
     *     ->withArtifactLocation('s3://bucket/path')
     *     ->withTag('team', 'ml-team')
     *     ->create();
     * ```
     */
    public function createExperimentBuilder(string $name): ExperimentBuilder
    {
        return new ExperimentBuilder($this->experiments(), $name);
    }

    /**
     * Create a model builder for fluent API
     *
     * @example
     * ```php
     * $model = $client->createModelBuilder('my-model')
     *     ->withDescription('Image classification model')
     *     ->withTag('framework', 'pytorch')
     *     ->create();
     * ```
     */
    public function createModelBuilder(string $name): ModelBuilder
    {
        return new ModelBuilder($this->modelRegistry(), $name);
    }

    /**
     * Get the Prompt API instance
     */
    public function prompts(): PromptApi
    {
        if ($this->promptApi === null) {
            $this->promptApi = new PromptApi($this->httpClient, $this->logger);
        }
        return $this->promptApi;
    }

    /**
     * Get the Webhook API instance
     */
    public function webhooks(): WebhookApi
    {
        if ($this->webhookApi === null) {
            $this->webhookApi = new WebhookApi($this->httpClient, $this->logger);
        }
        return $this->webhookApi;
    }

    /**
     * Get the Dataset API instance
     */
    public function datasets(): DatasetApi
    {
        if ($this->datasetApi === null) {
            $this->datasetApi = new DatasetApi($this->httpClient, $this->logger);
        }
        return $this->datasetApi;
    }
}