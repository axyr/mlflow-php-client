<?php

declare(strict_types=1);

namespace MLflow\Tests\Api;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use MLflow\Api\RunApi;
use MLflow\Enum\RunStatus;
use MLflow\Enum\ViewType;
use MLflow\Model\Run;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class RunApiTest extends TestCase
{
    private RunApi $api;

    /** @var ClientInterface&MockObject */
    private ClientInterface $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->api = new RunApi($this->httpClient);
    }

    public function test_create_run(): void
    {
        $expectedResponse = [
            'run' => [
                'info' => [
                    'run_id' => 'run123',
                    'experiment_id' => 'exp123',
                    'status' => 'RUNNING',
                    'start_time' => 1234567890,
                    'artifact_uri' => 'file://path',
                    'lifecycle_stage' => 'active',
                ],
                'data' => [
                    'metrics' => [],
                    'params' => [],
                    'tags' => [],
                ],
            ],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/runs/create',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));

                    return $json['experiment_id'] === 'exp123'
                        && isset($json['start_time'])
                        && isset($json['tags']);
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $run = $this->api->create('exp123', 'user1', 'test-run', ['key' => 'value']);

        $this->assertInstanceOf(Run::class, $run);
        $this->assertEquals('run123', $run->getRunId());
        $this->assertEquals('exp123', $run->getExperimentId());
        $this->assertEquals(RunStatus::RUNNING, $run->getStatus());
    }

    public function test_get_run_by_id(): void
    {
        $expectedResponse = [
            'run' => [
                'info' => [
                    'run_id' => 'run123',
                    'experiment_id' => 'exp123',
                    'status' => 'FINISHED',
                    'start_time' => 1234567890,
                    'end_time' => 1234567900,
                ],
                'data' => [
                    'metrics' => [
                        ['key' => 'accuracy', 'value' => 0.95, 'timestamp' => 1234567895, 'step' => 0],
                    ],
                    'params' => [
                        ['key' => 'learning_rate', 'value' => '0.01'],
                    ],
                    'tags' => [
                        ['key' => 'model_type', 'value' => 'neural_network'],
                    ],
                ],
            ],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'mlflow/runs/get',
                $this->callback(function (array $options): bool {
                    return $options['query']['run_id'] === 'run123';
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $run = $this->api->getById('run123');

        $this->assertInstanceOf(Run::class, $run);
        $this->assertEquals('run123', $run->getRunId());
        $this->assertEquals(RunStatus::FINISHED, $run->getStatus());
        $this->assertCount(1, $run->getMetrics());
        $this->assertCount(1, $run->getParams());
        $this->assertCount(1, $run->getTags());
    }

    public function test_search_runs(): void
    {
        $expectedResponse = [
            'runs' => [
                [
                    'info' => [
                        'run_id' => 'run1',
                        'experiment_id' => 'exp1',
                        'status' => 'FINISHED',
                        'start_time' => 1234567890,
                    ],
                    'data' => [],
                ],
                [
                    'info' => [
                        'run_id' => 'run2',
                        'experiment_id' => 'exp1',
                        'status' => 'RUNNING',
                        'start_time' => 1234567900,
                    ],
                    'data' => [],
                ],
            ],
            'next_page_token' => 'next_token',
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/runs/search',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));

                    return $json['experiment_ids'] === ['exp1']
                        && isset($json['filter']) && $json['filter'] === 'metrics.accuracy > 0.9'
                        && isset($json['run_view_type']) && $json['run_view_type'] === 'ACTIVE_ONLY'
                        && isset($json['max_results']) && $json['max_results'] === 100;
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $result = $this->api->search(
            ['exp1'],
            'metrics.accuracy > 0.9',
            ViewType::ACTIVE_ONLY,
            100
        );

        $this->assertIsArray($result);
        $this->assertCount(2, $result['runs']);
        $this->assertEquals('next_token', $result['next_page_token']);
        $this->assertInstanceOf(Run::class, $result['runs'][0]);
    }

    public function test_update_run(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/runs/update',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));

                    return $json['run_id'] === 'run123'
                        && isset($json['status']) && $json['status'] === 'FINISHED'
                        && isset($json['end_time']) && $json['end_time'] === 1234567900
                        && isset($json['run_name']) && $json['run_name'] === 'updated-name';
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->update('run123', RunStatus::FINISHED, 1234567900, 'updated-name');
        $this->assertTrue(true);
    }

    public function test_log_metric(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/runs/log-metric',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));

                    return $json['run_id'] === 'run123'
                        && isset($json['key']) && $json['key'] === 'accuracy'
                        && isset($json['value']) && $json['value'] === 0.95
                        && isset($json['timestamp'])
                        && isset($json['step']) && $json['step'] === 10;
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->logMetric('run123', 'accuracy', 0.95, null, 10);
        $this->assertTrue(true);
    }

    public function test_log_parameter(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/runs/log-parameter',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));

                    return $json['run_id'] === 'run123'
                        && isset($json['key']) && $json['key'] === 'learning_rate'
                        && isset($json['value']) && $json['value'] === '0.01';
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->logParameter('run123', 'learning_rate', '0.01');
        $this->assertTrue(true);
    }

    public function test_log_batch(): void
    {
        $metrics = [
            ['key' => 'accuracy', 'value' => 0.95, 'step' => 1],
            ['key' => 'loss', 'value' => 0.05, 'step' => 1],
        ];

        $params = [
            'learning_rate' => '0.01',
            'batch_size' => '32',
        ];

        $tags = [
            'model_type' => 'cnn',
            'dataset' => 'mnist',
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/runs/log-batch',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));

                    return $json['run_id'] === 'run123'
                        && is_array($json['metrics']) && count($json['metrics']) === 2
                        && is_array($json['params']) && count($json['params']) === 2
                        && is_array($json['tags']) && count($json['tags']) === 2;
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->logBatch('run123', $metrics, $params, $tags);
        $this->assertTrue(true);
    }

    public function test_set_and_delete_tag(): void
    {
        // Test set tag
        $this->httpClient
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnCallback(function ($method, $endpoint, $options) {
                if ($endpoint === 'mlflow/runs/set-tag') {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));
                    $this->assertEquals('run123', $json['run_id']);
                    $this->assertEquals('tag_key', $json['key']);
                    $this->assertEquals('tag_value', $json['value']);
                } elseif ($endpoint === 'mlflow/runs/delete-tag') {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));
                    $this->assertEquals('run123', $json['run_id']);
                    $this->assertEquals('tag_key', $json['key']);
                }

                return $this->createJsonResponse([]);
            });

        $this->api->setTag('run123', 'tag_key', 'tag_value');
        $this->api->deleteTag('run123', 'tag_key');
        $this->assertTrue(true);
    }

    public function test_delete_and_restore_run(): void
    {
        // Test delete
        $this->httpClient
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnCallback(function ($method, $endpoint, $options) {
                $json = json_decode($options['body'], true);
                assert(is_array($json));
                $this->assertEquals('run123', $json['run_id']);

                if ($endpoint === 'mlflow/runs/delete') {
                    // Delete endpoint
                } elseif ($endpoint === 'mlflow/runs/restore') {
                    // Restore endpoint
                }

                return $this->createJsonResponse([]);
            });

        $this->api->deleteRun('run123');
        $this->api->restore('run123');
        $this->assertTrue(true);
    }

    public function test_log_model(): void
    {
        $flavors = [
            'python_function' => [
                'loader_module' => 'mlflow.sklearn',
                'python_version' => '3.8',
            ],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/runs/log-model',
                $this->callback(function (array $options) use ($flavors): bool {
                    $json = json_decode($options['body'], true);

                    return is_array($json)
                        && isset($json['run_id'], $json['artifact_path'], $json['flavors'])
                        && isset($json['run_id']) && $json['run_id'] === 'run123'
                        && isset($json['artifact_path']) && $json['artifact_path'] === 'model'
                        && isset($json['flavors']) && $json['flavors'] === $flavors;
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->logModel('run123', 'model', $flavors);
        $this->assertTrue(true);
    }

    public function test_log_inputs(): void
    {
        $datasets = [
            [
                'dataset' => [
                    'name' => 'training_data',
                    'digest' => 'abc123',
                    'source_type' => 'local',
                ],
            ],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/runs/log-inputs',
                $this->callback(function (array $options) use ($datasets): bool {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));

                    return isset($json['run_id'], $json['datasets'])
                        && $json['run_id'] === 'run123'
                        && $json['datasets'] === $datasets;
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->logInputs('run123', $datasets);
        $this->assertTrue(true);
    }

    public function test_set_terminated(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/runs/update',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));

                    return $json['run_id'] === 'run123'
                        && isset($json['status']) && $json['status'] === RunStatus::FINISHED->value
                        && isset($json['end_time']);
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->setTerminated('run123');
        $this->assertTrue(true);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createJsonResponse(array $data, int $statusCode = 200): ResponseInterface
    {
        $json = json_encode($data);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode JSON');
        }

        return new Response(
            $statusCode,
            ['Content-Type' => 'application/json'],
            $json
        );
    }
}
