<?php

declare(strict_types=1);

namespace MLflow\Tests\Api;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use MLflow\Api\ModelRegistryApi;
use MLflow\Enum\ModelStage;
use MLflow\Model\ModelVersion;
use MLflow\Model\RegisteredModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ModelRegistryApiTest extends TestCase
{
    private ModelRegistryApi $api;

    /** @var ClientInterface&MockObject */
    private ClientInterface $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->api = new ModelRegistryApi($this->httpClient);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createJsonResponse(array $data, int $status = 200): ResponseInterface
    {
        $json = json_encode($data);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode JSON');
        }

        return new Response($status, ['Content-Type' => 'application/json'], $json);
    }

    public function test_create_registered_model(): void
    {
        $expectedResponse = [
            'registered_model' => [
                'name' => 'my-model',
                'description' => 'Test model',
                'creation_timestamp' => 1234567890000,
                'last_updated_timestamp' => 1234567890000,
            ],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/registered-models/create',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);

                    return is_array($json) && $json['name'] === 'my-model';
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $model = $this->api->createRegisteredModel('my-model', 'Test model', ['env' => 'test']);

        $this->assertInstanceOf(RegisteredModel::class, $model);
        $this->assertEquals('my-model', $model->name);
    }

    public function test_get_registered_model(): void
    {
        $expectedResponse = [
            'registered_model' => [
                'name' => 'my-model',
                'creation_timestamp' => 1234567890000,
                'last_updated_timestamp' => 1234567890000,
            ],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'mlflow/registered-models/get',
                $this->callback(function (array $options): bool {
                    return $options['query']['name'] === 'my-model';
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $model = $this->api->getRegisteredModel('my-model');

        $this->assertInstanceOf(RegisteredModel::class, $model);
        $this->assertEquals('my-model', $model->name);
    }

    public function test_delete_registered_model(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'DELETE',
                'mlflow/registered-models/delete',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);

                    return is_array($json) && $json['name'] === 'my-model';
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->deleteRegisteredModel('my-model');

        $this->assertTrue(true);
    }

    public function test_search_registered_models(): void
    {
        $expectedResponse = [
            'registered_models' => [
                [
                    'name' => 'model-1',
                    'creation_timestamp' => 1234567890000,
                    'last_updated_timestamp' => 1234567890000,
                ],
            ],
            'next_page_token' => 'token-abc',
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/registered-models/search',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);

                    return is_array($json) && isset($json['max_results']);
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $result = $this->api->searchRegisteredModels(maxResults: 100);

        $this->assertIsArray($result);
        $this->assertCount(1, $result['registered_models']);
        $this->assertEquals('token-abc', $result['next_page_token']);
    }

    public function test_create_model_version(): void
    {
        $expectedResponse = [
            'model_version' => [
                'name' => 'my-model',
                'version' => '1',
                'current_stage' => 'None',
                'source' => 's3://bucket/model',
                'creation_timestamp' => 1234567890000,
                'last_updated_timestamp' => 1234567890000,
            ],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/model-versions/create',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);

                    return is_array($json)
                        && $json['name'] === 'my-model'
                        && $json['source'] === 's3://bucket/model';
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $version = $this->api->createModelVersion('my-model', 's3://bucket/model');

        $this->assertInstanceOf(ModelVersion::class, $version);
        $this->assertEquals('my-model', $version->name);
        $this->assertEquals('1', $version->version);
    }

    public function test_get_model_version(): void
    {
        $expectedResponse = [
            'model_version' => [
                'name' => 'my-model',
                'version' => '1',
                'current_stage' => 'Production',
                'creation_timestamp' => 1234567890000,
                'last_updated_timestamp' => 1234567890000,
            ],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'mlflow/model-versions/get',
                $this->callback(function (array $options): bool {
                    return $options['query']['name'] === 'my-model'
                        && $options['query']['version'] === '1';
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $version = $this->api->getModelVersion('my-model', '1');

        $this->assertInstanceOf(ModelVersion::class, $version);
        $this->assertEquals('1', $version->version);
    }

    public function test_transition_model_version_stage(): void
    {
        $expectedResponse = [
            'model_version' => [
                'name' => 'my-model',
                'version' => '1',
                'current_stage' => 'Production',
                'creation_timestamp' => 1234567890000,
                'last_updated_timestamp' => 1234567891000,
            ],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/model-versions/transition-stage',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);

                    return is_array($json)
                        && $json['name'] === 'my-model'
                        && $json['version'] === '1'
                        && $json['stage'] === 'Production';
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $version = $this->api->transitionModelVersionStage(
            'my-model',
            '1',
            ModelStage::PRODUCTION
        );

        $this->assertEquals(ModelStage::PRODUCTION, $version->currentStage);
    }

    public function test_set_registered_model_alias(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/registered-models/set-alias',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);

                    return is_array($json)
                        && $json['name'] === 'my-model'
                        && $json['alias'] === 'champion'
                        && $json['version'] === '1';
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->setRegisteredModelAlias('my-model', 'champion', '1');

        $this->assertTrue(true);
    }

    public function test_get_model_version_by_alias(): void
    {
        $expectedResponse = [
            'model_version' => [
                'name' => 'my-model',
                'version' => '1',
                'current_stage' => 'Production',
                'creation_timestamp' => 1234567890000,
                'last_updated_timestamp' => 1234567890000,
            ],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'mlflow/registered-models/get-version-by-alias',
                $this->callback(function (array $options): bool {
                    return $options['query']['name'] === 'my-model'
                        && $options['query']['alias'] === 'champion';
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $version = $this->api->getModelVersionByAlias('my-model', 'champion');

        $this->assertInstanceOf(ModelVersion::class, $version);
        $this->assertEquals('1', $version->version);
    }
}
