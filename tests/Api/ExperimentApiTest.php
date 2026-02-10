<?php

declare(strict_types=1);

namespace MLflow\Tests\Api;

use MLflow\Api\ExperimentApi;
use MLflow\Model\Experiment;
use MLflow\Exception\MLflowException;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class ExperimentApiTest extends TestCase
{
    private ExperimentApi $api;
    private ClientInterface $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->api = new ExperimentApi($this->httpClient);
    }

    public function testCreateExperiment(): void
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
                $this->callback(function ($options) {
                    $json = json_decode($options['body'], true);
                    return $json['name'] === 'test-experiment'
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

    public function testGetExperimentById(): void
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
                $this->callback(function ($options) {
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

    public function testGetExperimentByName(): void
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
                $this->callback(function ($options) {
                    return $options['query']['experiment_name'] === 'test-experiment';
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $experiment = $this->api->getByName('test-experiment');

        $this->assertInstanceOf(Experiment::class, $experiment);
        $this->assertEquals('test-experiment', $experiment->getName());
    }

    public function testSearchExperiments(): void
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
                $this->callback(function ($options) {
                    $json = json_decode($options['body'], true);
                    return $json['filter'] === "attribute.name = 'test'"
                        && $json['max_results'] === 10
                        && $json['view_type'] === 'ACTIVE_ONLY';
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

    public function testUpdateExperiment(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/experiments/update',
                $this->callback(function ($options) {
                    $json = json_decode($options['body'], true);
                    return $json['experiment_id'] === '123'
                        && $json['new_name'] === 'new-name';
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->update('123', 'new-name');
        $this->assertTrue(true); // Just verify no exception thrown
    }

    public function testDeleteExperiment(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/experiments/delete',
                $this->callback(function ($options) {
                    $json = json_decode($options['body'], true);
                    return $json['experiment_id'] === '123';
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->deleteExperiment('123');
        $this->assertTrue(true);
    }

    public function testRestoreExperiment(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/experiments/restore',
                $this->callback(function ($options) {
                    $json = json_decode($options['body'], true);
                    return $json['experiment_id'] === '123';
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->restore('123');
        $this->assertTrue(true);
    }

    public function testSetExperimentTag(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/experiments/set-experiment-tag',
                $this->callback(function ($options) {
                    $json = json_decode($options['body'], true);
                    return $json['experiment_id'] === '123'
                        && $json['key'] === 'tag_key'
                        && $json['value'] === 'tag_value';
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->setTag('123', 'tag_key', 'tag_value');
        $this->assertTrue(true);
    }

    public function testDeleteExperimentTag(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/experiments/delete-experiment-tag',
                $this->callback(function ($options) {
                    $json = json_decode($options['body'], true);
                    return $json['experiment_id'] === '123'
                        && $json['key'] === 'tag_key';
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->deleteTag('123', 'tag_key');
        $this->assertTrue(true);
    }

    public function testExceptionHandling(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('Network error'));

        $this->expectException(MLflowException::class);
        $this->expectExceptionMessage('Request failed: Network error');

        $this->api->getById('123');
    }

    private function createJsonResponse(array $data, int $statusCode = 200): ResponseInterface
    {
        return new Response(
            $statusCode,
            ['Content-Type' => 'application/json'],
            json_encode($data)
        );
    }
}