<?php

declare(strict_types=1);

namespace MLflow\Tests\Api;

use MLflow\Api\PromptApi;
use MLflow\Model\Prompt;
use MLflow\Model\PromptVersion;
use MLflow\Exception\MLflowException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class PromptApiTest extends TestCase
{
    private PromptApi $api;
    /** @var ClientInterface&MockObject */
    private ClientInterface $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->api = new PromptApi($this->httpClient);
    }

    public function testCreatePrompt(): void
    {
        $expectedResponse = [
            'prompt' => [
                'name' => 'test-prompt',
                'description' => 'Test description',
                'tags' => ['key' => 'value'],
            ],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/prompts/create',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));
                    return $json['name'] === 'test-prompt'
                        && isset($json['description'])
                        && isset($json['tags']);
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $prompt = $this->api->createPrompt('test-prompt', 'Test description', ['key' => 'value']);

        $this->assertInstanceOf(Prompt::class, $prompt);
        $this->assertEquals('test-prompt', $prompt->getName());
    }

    public function testCreatePromptVersion(): void
    {
        $expectedResponse = [
            'prompt_version' => [
                'name' => 'test-prompt',
                'version' => '1',
                'template' => 'Hello {{name}}',
            ],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/prompts/versions/create',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));
                    return $json['name'] === 'test-prompt'
                        && $json['template'] === 'Hello {{name}}';
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $version = $this->api->createPromptVersion('test-prompt', 'Hello {{name}}');

        $this->assertInstanceOf(PromptVersion::class, $version);
        $this->assertEquals('test-prompt', $version->getName());
        $this->assertEquals('1', $version->getVersion());
    }

    public function testGetPrompt(): void
    {
        $expectedResponse = [
            'prompt' => [
                'name' => 'test-prompt',
                'description' => 'Test',
            ],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'mlflow/prompts/get',
                $this->callback(function (array $options): bool {
                    return $options['query']['name'] === 'test-prompt';
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $prompt = $this->api->getPrompt('test-prompt');

        $this->assertInstanceOf(Prompt::class, $prompt);
        $this->assertEquals('test-prompt', $prompt->getName());
    }

    public function testGetPromptVersion(): void
    {
        $expectedResponse = [
            'prompt_version' => [
                'name' => 'test-prompt',
                'version' => '1',
                'template' => 'Hello {{name}}',
            ],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'mlflow/prompts/versions/get',
                $this->callback(function (array $options): bool {
                    return $options['query']['name'] === 'test-prompt'
                        && $options['query']['version'] === '1';
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $version = $this->api->getPromptVersion('test-prompt', '1');

        $this->assertInstanceOf(PromptVersion::class, $version);
        $this->assertEquals('1', $version->getVersion());
    }

    public function testSearchPromptVersions(): void
    {
        $expectedResponse = [
            'prompt_versions' => [
                [
                    'name' => 'prompt1',
                    'version' => '1',
                    'template' => 'Template 1',
                ],
                [
                    'name' => 'prompt2',
                    'version' => '2',
                    'template' => 'Template 2',
                ],
            ],
            'next_page_token' => 'token123',
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/prompts/versions/search',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));
                    return isset($json['max_results']) && $json['max_results'] === 100;
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $result = $this->api->searchPromptVersions(null, 100);

        $this->assertCount(2, $result['prompt_versions']);
        $this->assertEquals('token123', $result['next_page_token']);
        $this->assertInstanceOf(PromptVersion::class, $result['prompt_versions'][0]);
    }

    public function testDeletePrompt(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'DELETE',
                'mlflow/prompts/delete',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));
                    return $json['name'] === 'test-prompt';
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->deletePrompt('test-prompt');
        $this->assertTrue(true);
    }

    public function testSetPromptAlias(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/prompts/versions/set-alias',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));
                    return $json['name'] === 'test-prompt'
                        && $json['alias'] === 'production'
                        && $json['version'] === '1';
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->setPromptAlias('test-prompt', 'production', '1');
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
