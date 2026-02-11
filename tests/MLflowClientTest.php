<?php

declare(strict_types=1);

namespace MLflow\Tests;

use MLflow\MLflowClient;
use MLflow\Api\ExperimentApi;
use MLflow\Api\RunApi;
use MLflow\Api\ModelRegistryApi;
use MLflow\Api\MetricApi;
use MLflow\Api\ArtifactApi;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

class MLflowClientTest extends TestCase
{
    private MLflowClient $client;

    protected function setUp(): void
    {
        $this->client = new MLflowClient('http://localhost:5000');
    }

    public function testClientInitialization(): void
    {
        $this->assertInstanceOf(MLflowClient::class, $this->client);
        $this->assertEquals('http://localhost:5000', $this->client->getTrackingUri());
    }

    public function testExperimentsApiAccess(): void
    {
        $api = $this->client->experiments();
        $this->assertInstanceOf(ExperimentApi::class, $api);

        // Ensure singleton pattern
        $this->assertSame($api, $this->client->experiments());
    }

    public function testRunsApiAccess(): void
    {
        $api = $this->client->runs();
        $this->assertInstanceOf(RunApi::class, $api);
        $this->assertSame($api, $this->client->runs());
    }

    public function testModelsApiAccess(): void
    {
        $api = $this->client->models();
        $this->assertInstanceOf(ModelRegistryApi::class, $api);
        $this->assertSame($api, $this->client->models());
    }

    public function testModelRegistryApiAccess(): void
    {
        $api = $this->client->modelRegistry();
        $this->assertInstanceOf(ModelRegistryApi::class, $api);
        $this->assertSame($api, $this->client->modelRegistry());
        // Test that models() and modelRegistry() return the same instance
        $this->assertSame($api, $this->client->models());
    }

    public function testMetricsApiAccess(): void
    {
        $api = $this->client->metrics();
        $this->assertInstanceOf(MetricApi::class, $api);
        $this->assertSame($api, $this->client->metrics());
    }

    public function testArtifactsApiAccess(): void
    {
        $api = $this->client->artifacts();
        $this->assertInstanceOf(ArtifactApi::class, $api);
        $this->assertSame($api, $this->client->artifacts());
    }

    public function testCustomHttpClient(): void
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

    public function testCustomLogger(): void
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
            ->method('info')
            ->with('MLflow client initialized', $this->anything());

        new MLflowClient('http://localhost:5000', [], $mockLogger);
    }

    public function testTrailingSlashRemoval(): void
    {
        $client = new MLflowClient('http://localhost:5000/');
        $this->assertEquals('http://localhost:5000', $client->getTrackingUri());
    }
}
