# Integration Tests

Integration tests verify the MLflow PHP client against a real MLflow server.

## Requirements

- Docker and Docker Compose
- Make (optional, for convenience commands)

## Running Integration Tests

### Using Docker Compose

Start MLflow server and run tests:

```bash
docker-compose -f docker-compose.test.yml up --abort-on-container-exit
```

### Running Tests Manually

1. Start MLflow server:
```bash
docker-compose -f docker-compose.test.yml up mlflow
```

2. In another terminal, run integration tests:
```bash
export MLFLOW_TRACKING_URI=http://localhost:5000
vendor/bin/phpunit tests/Integration
```

3. Stop MLflow server:
```bash
docker-compose -f docker-compose.test.yml down -v
```

## Test Structure

- `IntegrationTestCase.php` - Base class for integration tests
  - Validates connection to MLflow server
  - Creates temporary experiment for each test
  - Cleans up after tests complete

- `RunLifecycleTest.php` - Tests for run operations
  - Complete run lifecycle (create, log, update, terminate)
  - Batch logging
  - RunBuilder integration

- `ExperimentLifecycleTest.php` - Tests for experiment operations
  - Create and retrieve experiments
  - ExperimentBuilder integration
  - Search functionality

## Writing New Integration Tests

1. Extend `IntegrationTestCase`
2. Use `$this->client` to access MLflowClient
3. Use `$this->testExperimentId` for test experiment
4. Use `$this->createTestRun()` helper for quick run creation
5. Tests will auto-cleanup on tearDown

Example:
```php
<?php

namespace MLflow\Tests\Integration;

final class MyIntegrationTest extends IntegrationTestCase
{
    public function test_my_feature(): void
    {
        $runId = $this->createTestRun();

        // Your test code here
        $this->client->runs()->logMetric($runId, 'test', 1.0);

        $run = $this->client->runs()->getById($runId);
        $this->assertNotNull($run);
    }
}
```

## Troubleshooting

**Tests are skipped**: Make sure MLFLOW_TRACKING_URI is set and MLflow server is running.

**Connection refused**: Wait a few seconds for MLflow server to fully start. The health check should handle this automatically in Docker Compose.

**Tests fail with 404**: Ensure you're using a compatible MLflow version (2.0+).
