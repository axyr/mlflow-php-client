<?php

declare(strict_types=1);

namespace MLflow\Tests;

use GuzzleHttp\ClientInterface;
use MLflow\Api\ArtifactApi;
use MLflow\Api\ExperimentApi;
use MLflow\Api\MetricApi;
use MLflow\Api\ModelRegistryApi;
use MLflow\Api\RunApi;
use MLflow\MLflowClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MLflowClientTest extends TestCase
{
    private MLflowClient $client;

    protected function setUp(): void
    {
        $this->client = new MLflowClient('http://localhost:5000');
    }

    public function test_client_initialization(): void
    {
        $this->assertInstanceOf(MLflowClient::class, $this->client);
        $this->assertEquals('http://localhost:5000', $this->client->getTrackingUri());
    }

    public function test_experiments_api_access(): void
    {
        $api = $this->client->experiments();
        $this->assertInstanceOf(ExperimentApi::class, $api);

        // Ensure singleton pattern
        $this->assertSame($api, $this->client->experiments());
    }

    public function test_runs_api_access(): void
    {
        $api = $this->client->runs();
        $this->assertInstanceOf(RunApi::class, $api);
        $this->assertSame($api, $this->client->runs());
    }

    public function test_models_api_access(): void
    {
        $api = $this->client->models();
        $this->assertInstanceOf(ModelRegistryApi::class, $api);
        $this->assertSame($api, $this->client->models());
    }

    public function test_model_registry_api_access(): void
    {
        $api = $this->client->modelRegistry();
        $this->assertInstanceOf(ModelRegistryApi::class, $api);
        $this->assertSame($api, $this->client->modelRegistry());
        // Test that models() and modelRegistry() return the same instance
        $this->assertSame($api, $this->client->models());
    }

    public function test_metrics_api_access(): void
    {
        $api = $this->client->metrics();
        $this->assertInstanceOf(MetricApi::class, $api);
        $this->assertSame($api, $this->client->metrics());
    }

    public function test_artifacts_api_access(): void
    {
        $api = $this->client->artifacts();
        $this->assertInstanceOf(ArtifactApi::class, $api);
        $this->assertSame($api, $this->client->artifacts());
    }

    public function test_custom_http_client(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $this->client->setHttpClient($mockClient);

        // After setting new client, API instances should be reset
        $api1 = $this->client->experiments();
        $this->client->setHttpClient($mockClient);
        $api2 = $this->client->experiments();

        // These should be different instances after reset
        $this->assertNotSame($api1, $api2);
    }

    public function test_custom_logger(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
            ->method('info')
            ->with('MLflow client initialized', $this->anything());

        new MLflowClient('http://localhost:5000', [], $mockLogger);
    }

    public function test_trailing_slash_removal(): void
    {
        $client = new MLflowClient('http://localhost:5000/');
        $this->assertEquals('http://localhost:5000', $client->getTrackingUri());
    }
}
