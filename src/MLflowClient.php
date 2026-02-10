<?php

declare(strict_types=1);

namespace MLflow;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface;
use MLflow\Api\ExperimentApi;
use MLflow\Api\RunApi;
use MLflow\Api\ModelRegistryApi;
use MLflow\Api\MetricApi;
use MLflow\Api\ArtifactApi;
use MLflow\Api\TraceApi;
use MLflow\Builder\TraceBuilder;
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

    /**
     * MLflowClient constructor.
     *
     * @param string $trackingUri The MLflow tracking server URI
     * @param array<string, mixed> $config Optional Guzzle client configuration
     * @param LoggerInterface|null $logger PSR-3 compatible logger
     */
    public function __construct(
        string $trackingUri,
        array $config = [],
        ?LoggerInterface $logger = null
    ) {
        $this->trackingUri = rtrim($trackingUri, '/');
        $this->logger = $logger ?? new NullLogger();

        $defaultConfig = [
            'base_uri' => $this->trackingUri . '/api/2.0/',
            'timeout' => 30.0,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ];

        $this->httpClient = new HttpClient(array_merge($defaultConfig, $config));

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
}