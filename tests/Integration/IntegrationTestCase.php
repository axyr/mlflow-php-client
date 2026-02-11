<?php

declare(strict_types=1);

namespace MLflow\Tests\Integration;

use MLflow\MLflowClient;
use PHPUnit\Framework\TestCase;

/**
 * Base class for integration tests
 *
 * Integration tests require a running MLflow server.
 * Set MLFLOW_TRACKING_URI environment variable to point to the server.
 */
abstract class IntegrationTestCase extends TestCase
{
    protected MLflowClient $client;
    protected string $testExperimentId;
    protected string $testExperimentName;

    protected function setUp(): void
    {
        parent::setUp();

        // Get tracking URI from environment
        $trackingUri = getenv('MLFLOW_TRACKING_URI');

        if ($trackingUri === false) {
            $this->markTestSkipped(
                'Integration tests require MLFLOW_TRACKING_URI environment variable. '
                . 'Run: docker-compose -f docker-compose.test.yml up'
            );
        }

        $this->client = new MLflowClient($trackingUri);

        // Validate connection
        try {
            $this->client->validateConnection();
        } catch (\Exception $e) {
            $this->markTestSkipped(
                "Cannot connect to MLflow server at {$trackingUri}: {$e->getMessage()}"
            );
        }

        // Create test experiment
        $this->testExperimentName = 'test-integration-' . uniqid();
        $experiment = $this->client->experiments()->create(
            $this->testExperimentName,
            tags: ['test' => 'true', 'created_by' => 'phpunit']
        );

        $this->testExperimentId = $experiment->experimentId;
    }

    protected function tearDown(): void
    {
        // Clean up test experiment
        if (isset($this->testExperimentId)) {
            try {
                $this->client->experiments()->deleteExperiment($this->testExperimentId);
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }

        parent::tearDown();
    }

    /**
     * Helper to create a test run
     *
     * @param array<string, string> $tags
     */
    protected function createTestRun(array $tags = []): string
    {
        $run = $this->client->runs()->create(
            experimentId: $this->testExperimentId,
            runName: 'test-run-' . uniqid(),
            tags: array_merge(['test' => 'true'], $tags)
        );

        return $run->info->runId;
    }
}
