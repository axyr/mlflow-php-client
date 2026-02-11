<?php

declare(strict_types=1);

namespace MLflow\Tests\Api;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use MLflow\Api\ExperimentApi;
use MLflow\Exception\MLflowException;
use MLflow\Model\Experiment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ExperimentApiTest extends TestCase
{
    private ExperimentApi $api;

    /** @var ClientInterface&MockObject */
    private ClientInterface $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->api = new ExperimentApi($this->httpClient);
    }

    public function test_create_experiment(): void
    {
        $expectedResponse = [
            'experiment_id' => '123',
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/experiments/create',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);

                    return is_array($json)
                        && isset($json['name'])
                        && isset($json['name']) && $json['name'] === 'test-experiment'
                        && isset($json['artifact_location'])
                        && isset($json['tags']);
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $experiment = $this->api->create(
            'test-experiment',
            's3://bucket/path',
            ['key1' => 'value1']
        );

        $this->assertInstanceOf(Experiment::class, $experiment);
        $this->assertEquals('123', $experiment->getExperimentId());
        $this->assertEquals('test-experiment', $experiment->getName());
    }

    public function test_get_experiment_by_id(): void
    {
        $expectedResponse = [
            'experiment' => [
                'experiment_id' => '123',
                'name' => 'test-experiment',
                'artifact_location' => 's3://bucket/path',
                'lifecycle_stage' => 'active',
            ],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'mlflow/experiments/get',
                $this->callback(function (array $options): bool {
                    return $options['query']['experiment_id'] === '123';
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $experiment = $this->api->getById('123');

        $this->assertInstanceOf(Experiment::class, $experiment);
        $this->assertEquals('123', $experiment->getExperimentId());
        $this->assertEquals('test-experiment', $experiment->getName());
        $this->assertTrue($experiment->isActive());
    }

    public function test_get_experiment_by_name(): void
    {
        $expectedResponse = [
            'experiment' => [
                'experiment_id' => '123',
                'name' => 'test-experiment',
                'lifecycle_stage' => 'active',
            ],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'mlflow/experiments/get-by-name',
                $this->callback(function (array $options): bool {
                    return $options['query']['experiment_name'] === 'test-experiment';
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $experiment = $this->api->getByName('test-experiment');

        $this->assertInstanceOf(Experiment::class, $experiment);
        $this->assertEquals('test-experiment', $experiment->getName());
    }

    public function test_search_experiments(): void
    {
        $expectedResponse = [
            'experiments' => [
                [
                    'experiment_id' => '1',
                    'name' => 'exp1',
                ],
                [
                    'experiment_id' => '2',
                    'name' => 'exp2',
                ],
            ],
            'next_page_token' => 'token123',
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/experiments/search',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));

                    return $json['filter'] === "attribute.name = 'test'"
                        && isset($json['max_results']) && $json['max_results'] === 10
                        && isset($json['view_type']) && $json['view_type'] === 'ACTIVE_ONLY';
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $result = $this->api->search(
            "attribute.name = 'test'",
            10,
            null,
            ['name DESC']
        );

        $this->assertIsArray($result);
        $this->assertCount(2, $result['experiments']);
        $this->assertEquals('token123', $result['next_page_token']);
        $this->assertInstanceOf(Experiment::class, $result['experiments'][0]);
    }

    public function test_update_experiment(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/experiments/update',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);

                    return is_array($json) && isset($json['experiment_id'])
                        && $json['experiment_id'] === '123'
                        && isset($json['new_name']) && $json['new_name'] === 'new-name';
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->update('123', 'new-name');
        $this->assertTrue(true); // Just verify no exception thrown
    }

    public function test_delete_experiment(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/experiments/delete',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));

                    return $json['experiment_id'] === '123';
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->deleteExperiment('123');
        $this->assertTrue(true);
    }

    public function test_restore_experiment(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/experiments/restore',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));

                    return $json['experiment_id'] === '123';
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->restore('123');
        $this->assertTrue(true);
    }

    public function test_set_experiment_tag(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/experiments/set-experiment-tag',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);

                    return is_array($json) && isset($json['experiment_id'])
                        && $json['experiment_id'] === '123' && isset($json['key'])
                        && $json['key'] === 'tag_key' && isset($json['value'])
                        && $json['value'] === 'tag_value';
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->setTag('123', 'tag_key', 'tag_value');
        $this->assertTrue(true);
    }

    public function test_delete_experiment_tag(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/experiments/delete-experiment-tag',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);

                    return is_array($json) && isset($json['experiment_id'])
                        && $json['experiment_id'] === '123'
                        && isset($json['key']) && $json['key'] === 'tag_key';
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->deleteTag('123', 'tag_key');
        $this->assertTrue(true);
    }

    public function test_exception_handling(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('Network error'));

        $this->expectException(MLflowException::class);
        $this->expectExceptionMessage('Request failed: Network error');

        $this->api->getById('123');
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
