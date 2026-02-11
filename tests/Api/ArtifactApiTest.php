<?php

declare(strict_types=1);

namespace MLflow\Tests\Api;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use MLflow\Api\ArtifactApi;
use MLflow\Model\FileInfo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ArtifactApiTest extends TestCase
{
    private ArtifactApi $api;
    /** @var ClientInterface&MockObject */
    private ClientInterface $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->api = new ArtifactApi($this->httpClient);
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

    public function testList(): void
    {
        $expectedResponse = [
            'root_uri' => 's3://bucket/artifacts',
            'files' => [
                [
                    'path' => 'model.pkl',
                    'is_dir' => false,
                    'file_size' => 1024,
                ],
                [
                    'path' => 'models/',
                    'is_dir' => true,
                ],
            ],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'mlflow/artifacts/list',
                $this->callback(function (array $options): bool {
                    return $options['query']['run_id'] === 'run-123'
                        && $options['query']['path'] === 'models/';
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $result = $this->api->list('run-123', 'models/');

        $this->assertIsArray($result);
        $this->assertEquals('s3://bucket/artifacts', $result['root_uri']);
        $this->assertCount(2, $result['files']);
        $this->assertInstanceOf(FileInfo::class, $result['files'][0]);
        $this->assertEquals('model.pkl', $result['files'][0]->path);
        $this->assertFalse($result['files'][0]->isDir);
    }

    public function testListRootPath(): void
    {
        $expectedResponse = [
            'root_uri' => 'file:///mlruns',
            'files' => [],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'mlflow/artifacts/list',
                $this->callback(function (array $options): bool {
                    return $options['query']['run_id'] === 'run-123'
                        && !isset($options['query']['path']);
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $result = $this->api->list('run-123');

        $this->assertIsArray($result);
        $this->assertCount(0, $result['files']);
    }

    public function testGetDownloadUri(): void
    {
        $expectedResponse = [
            'artifact_uri' => 's3://bucket/artifacts/run-123/model.pkl',
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'mlflow/artifacts/get-artifact',
                $this->callback(function (array $options): bool {
                    return $options['query']['run_id'] === 'run-123'
                        && $options['query']['path'] === 'model.pkl';
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $uri = $this->api->getDownloadUri('run-123', 'model.pkl');

        $this->assertEquals('s3://bucket/artifacts/run-123/model.pkl', $uri);
    }

    public function testLogArtifactThrowsForNonExistentFile(): void
    {
        $this->expectException(\MLflow\Exception\MLflowException::class);
        $this->expectExceptionMessage('File not found');

        $this->api->logArtifact('run-123', '/non/existent/file.txt');
    }

    public function testLogArtifactsThrowsForNonExistentDir(): void
    {
        $this->expectException(\MLflow\Exception\MLflowException::class);
        $this->expectExceptionMessage('Directory not found');

        $this->api->logArtifacts('run-123', '/non/existent/dir');
    }
}
