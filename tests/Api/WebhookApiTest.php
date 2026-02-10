<?php

declare(strict_types=1);

namespace MLflow\Tests\Api;

use MLflow\Api\WebhookApi;
use MLflow\Model\Webhook;
use MLflow\Exception\MLflowException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class WebhookApiTest extends TestCase
{
    private WebhookApi $api;
    /** @var ClientInterface&MockObject */
    private ClientInterface $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->api = new WebhookApi($this->httpClient);
    }

    public function testCreateWebhook(): void
    {
        $expectedResponse = [
            'webhook' => [
                'id' => 'webhook123',
                'name' => 'test-webhook',
                'url' => 'https://example.com/webhook',
                'events' => ['MODEL_REGISTERED'],
                'status' => 'ACTIVE',
            ],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/registry-webhooks/create',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));
                    return $json['name'] === 'test-webhook'
                        && $json['url'] === 'https://example.com/webhook'
                        && is_array($json['events']) && in_array('MODEL_REGISTERED', $json['events']);
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $webhook = $this->api->createWebhook(
            'test-webhook',
            'https://example.com/webhook',
            ['MODEL_REGISTERED']
        );

        $this->assertInstanceOf(Webhook::class, $webhook);
        $this->assertEquals('webhook123', $webhook->getId());
        $this->assertEquals('test-webhook', $webhook->getName());
        $this->assertTrue($webhook->isActive());
    }

    public function testListWebhooks(): void
    {
        $expectedResponse = [
            'webhooks' => [
                [
                    'id' => 'webhook1',
                    'name' => 'webhook-1',
                    'url' => 'https://example.com/1',
                    'events' => ['MODEL_REGISTERED'],
                ],
                [
                    'id' => 'webhook2',
                    'name' => 'webhook-2',
                    'url' => 'https://example.com/2',
                    'events' => ['MODEL_VERSION_CREATED'],
                ],
            ],
            'next_page_token' => 'token123',
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'mlflow/registry-webhooks/list',
                $this->callback(function (array $options): bool {
                    return true;
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $result = $this->api->listWebhooks();

        $this->assertCount(2, $result['webhooks']);
        $this->assertEquals('token123', $result['next_page_token']);
        $this->assertInstanceOf(Webhook::class, $result['webhooks'][0]);
    }

    public function testGetWebhook(): void
    {
        $expectedResponse = [
            'webhook' => [
                'id' => 'webhook123',
                'name' => 'test-webhook',
                'url' => 'https://example.com/webhook',
                'events' => ['MODEL_REGISTERED'],
            ],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'mlflow/registry-webhooks/get',
                $this->callback(function (array $options): bool {
                    return $options['query']['id'] === 'webhook123';
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $webhook = $this->api->getWebhook('webhook123');

        $this->assertInstanceOf(Webhook::class, $webhook);
        $this->assertEquals('webhook123', $webhook->getId());
    }

    public function testDeleteWebhook(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'DELETE',
                'mlflow/registry-webhooks/delete',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));
                    return $json['id'] === 'webhook123';
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->deleteWebhook('webhook123');
        $this->assertTrue(true);
    }

    public function testUpdateWebhook(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'PATCH',
                'mlflow/registry-webhooks/update',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);
                    assert(is_array($json));
                    return $json['id'] === 'webhook123'
                        && isset($json['status']) && $json['status'] === 'INACTIVE';
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->updateWebhook('webhook123', null, null, 'INACTIVE');
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
