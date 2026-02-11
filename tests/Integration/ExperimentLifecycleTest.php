<?php

declare(strict_types=1);

namespace MLflow\Tests\Integration;

/**
 * Integration test for experiment operations
 */
final class ExperimentLifecycleTest extends IntegrationTestCase
{
    public function testCreateAndRetrieveExperiment(): void
    {
        $experiment = $this->client->experiments()->create(
            'test-exp-' . uniqid(),
            tags: ['team' => 'ml-team', 'project' => 'test']
        );

        $this->assertNotEmpty($experiment->experimentId);
        $this->assertNotEmpty($experiment->name);

        // Retrieve by ID
        $retrieved = $this->client->experiments()->getById($experiment->experimentId);
        $this->assertEquals($experiment->experimentId, $retrieved->experimentId);
        $this->assertEquals($experiment->name, $retrieved->name);

        // Retrieve by name
        $retrievedByName = $this->client->experiments()->getByName($experiment->name);
        $this->assertEquals($experiment->experimentId, $retrievedByName->experimentId);

        // Cleanup
        $this->client->experiments()->deleteExperiment($experiment->experimentId);
    }

    public function testExperimentBuilder(): void
    {
        $experiment = $this->client->createExperimentBuilder('builder-exp-' . uniqid())
            ->withTag('created_by', 'builder')
            ->withTag('test', 'true')
            ->create();

        $this->assertNotEmpty($experiment->experimentId);

        // Cleanup
        $this->client->experiments()->deleteExperiment($experiment->experimentId);
    }

    public function testSearchExperiments(): void
    {
        // Create multiple experiments
        $exp1 = $this->client->experiments()->create(
            'search-test-1-' . uniqid(),
            tags: ['type' => 'search-test']
        );

        $exp2 = $this->client->experiments()->create(
            'search-test-2-' . uniqid(),
            tags: ['type' => 'search-test']
        );

        // Search
        $results = $this->client->experiments()->search(
            maxResults: 100,
            orderBy: ['creation_time DESC']
        );

        $this->assertNotEmpty($results['experiments']);
        $this->assertIsArray($results['experiments']);

        // Cleanup
        $this->client->experiments()->deleteExperiment($exp1->experimentId);
        $this->client->experiments()->deleteExperiment($exp2->experimentId);
    }
}
