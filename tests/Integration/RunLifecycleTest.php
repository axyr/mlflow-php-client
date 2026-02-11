<?php

declare(strict_types=1);

namespace MLflow\Tests\Integration;

use MLflow\Enum\RunStatus;

/**
 * Integration test for complete run lifecycle
 */
final class RunLifecycleTest extends IntegrationTestCase
{
    public function testCompleteRunLifecycle(): void
    {
        // Create a run
        $run = $this->client->runs()->create(
            experimentId: $this->testExperimentId,
            runName: 'integration-test-run',
            tags: ['test' => 'lifecycle', 'framework' => 'phpunit']
        );

        $this->assertNotEmpty($run->info->runId);
        $this->assertEquals($this->testExperimentId, $run->info->experimentId);
        $this->assertEquals('integration-test-run', $run->info->runName);

        // Log parameters
        $this->client->runs()->logParameter($run->info->runId, 'learning_rate', '0.01');
        $this->client->runs()->logParameter($run->info->runId, 'batch_size', '32');

        // Log metrics
        $this->client->runs()->logMetric($run->info->runId, 'accuracy', 0.95);
        $this->client->runs()->logMetric($run->info->runId, 'loss', 0.05);

        // Retrieve run and verify data
        $retrieved = $this->client->runs()->getById($run->info->runId);

        $this->assertNotNull($retrieved->data);
        $this->assertCount(2, $retrieved->data->params);
        $this->assertCount(2, $retrieved->data->metrics);

        // Verify parameter values
        $this->assertTrue($retrieved->data->params->has('learning_rate'));
        $this->assertEquals('0.01', $retrieved->data->params->get('learning_rate')?->value);

        // Verify metric values
        $accuracyMetrics = $retrieved->data->metrics->getByKey('accuracy');
        $this->assertCount(1, $accuracyMetrics);
        $this->assertEquals(0.95, $accuracyMetrics->first()?->value);

        // Update run status
        $this->client->runs()->setTerminated($run->info->runId, RunStatus::FINISHED);

        // Verify final state
        $final = $this->client->runs()->getById($run->info->runId);
        $this->assertEquals(RunStatus::FINISHED, $final->info->status);
        $this->assertNotNull($final->info->endTime);
    }

    public function testBatchLogging(): void
    {
        $runId = $this->createTestRun();

        // Log batch data
        $this->client->runs()->logBatch(
            runId: $runId,
            metrics: [
                ['key' => 'accuracy', 'value' => 0.95, 'step' => 1],
                ['key' => 'accuracy', 'value' => 0.96, 'step' => 2],
                ['key' => 'accuracy', 'value' => 0.97, 'step' => 3],
                ['key' => 'loss', 'value' => 0.05, 'step' => 1],
                ['key' => 'loss', 'value' => 0.04, 'step' => 2],
            ],
            params: [
                'learning_rate' => '0.01',
                'optimizer' => 'adam',
                'epochs' => '10',
            ],
            tags: [
                'model_type' => 'neural_network',
            ]
        );

        // Verify
        $run = $this->client->runs()->getById($runId);

        $this->assertCount(3, $run->data->params);
        $this->assertGreaterThanOrEqual(5, $run->data->metrics->count());

        // Verify metric history
        $accuracyMetrics = $run->data->metrics->getByKey('accuracy');
        $this->assertCount(3, $accuracyMetrics);
    }

    public function testRunBuilderIntegration(): void
    {
        // Use builder to create and configure run
        $run = $this->client->createRunBuilder($this->testExperimentId)
            ->withName('builder-test-run')
            ->withParam('learning_rate', '0.001')
            ->withParam('batch_size', '64')
            ->withMetric('accuracy', 0.98, step: 1)
            ->withMetric('loss', 0.02, step: 1)
            ->withTag('framework', 'phpunit')
            ->withTag('builder', 'true')
            ->start();

        $this->assertNotEmpty($run->info->runId);
        $this->assertEquals('builder-test-run', $run->info->runName);

        // Verify data was logged
        $this->assertCount(2, $run->data->params);
        $this->assertGreaterThanOrEqual(2, $run->data->metrics->count());
    }
}
