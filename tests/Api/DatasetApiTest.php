<?php

declare(strict_types=1);

namespace MLflow\Tests\Api;

use MLflow\Api\DatasetApi;
use MLflow\Model\Dataset;
use MLflow\Exception\MLflowException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class DatasetApiTest extends TestCase
{
    private DatasetApi $api;
    /** @var ClientInterface&MockObject */
    private ClientInterface $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->api = new DatasetApi($this->httpClient);
    }

    public function testCreateDataset(): void
    {
        $expectedResponse = [
            'dataset' => [
                'dataset_id' => 'dataset123',
                'name' => 'test-dataset',
                'experiment_id' => 'exp123',
                'tags' => ['key' => 'value'],
            ],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/datasets/create',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));
                    return $json['name'] === 'test-dataset'
                        && isset($json['experiment_id'])
                        && isset($json['tags']);
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $dataset = $this->api->createDataset('test-dataset', 'exp123', ['key' => 'value']);

        $this->assertInstanceOf(Dataset::class, $dataset);
        $this->assertEquals('dataset123', $dataset->getDatasetId());
        $this->assertEquals('test-dataset', $dataset->getName());
    }

    public function testGetDataset(): void
    {
        $expectedResponse = [
            'dataset' => [
                'dataset_id' => 'dataset123',
                'name' => 'test-dataset',
            ],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'mlflow/datasets/get',
                $this->callback(function (array $options): bool {
                    return $options['query']['dataset_id'] === 'dataset123';
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $dataset = $this->api->getDataset('dataset123');

        $this->assertInstanceOf(Dataset::class, $dataset);
        $this->assertEquals('dataset123', $dataset->getDatasetId());
    }

    public function testAddDatasetToExperiments(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/datasets/add-to-experiments',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));
                    return $json['dataset_id'] === 'dataset123'
                        && is_array($json['experiment_ids'])
                        && in_array('exp1', $json['experiment_ids']);
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->addDatasetToExperiments('dataset123', ['exp1', 'exp2']);
        $this->assertTrue(true);
    }

    public function testSearchDatasets(): void
    {
        $expectedResponse = [
            'datasets' => [
                [
                    'dataset_id' => 'dataset1',
                    'name' => 'dataset-1',
                ],
                [
                    'dataset_id' => 'dataset2',
                    'name' => 'dataset-2',
                ],
            ],
            'next_page_token' => 'token123',
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/datasets/search',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));
                    return isset($json['max_results']) && $json['max_results'] === 100;
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $result = $this->api->searchDatasets(null, null, 100);

        $this->assertCount(2, $result['datasets']);
        $this->assertEquals('token123', $result['next_page_token']);
        $this->assertInstanceOf(Dataset::class, $result['datasets'][0]);
    }

    public function testDeleteDataset(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'DELETE',
                'mlflow/datasets/delete',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));
                    return $json['dataset_id'] === 'dataset123';
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->deleteDataset('dataset123');
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
