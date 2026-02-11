<?php

declare(strict_types=1);

namespace MLflow\Tests\Api;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use MLflow\Api\TraceApi;
use MLflow\Enum\TraceState;
use MLflow\Model\Trace;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class TraceApiTest extends TestCase
{
    private TraceApi $api;

    /** @var ClientInterface&MockObject */
    private ClientInterface $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->api = new TraceApi($this->httpClient);
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

    public function test_get_trace(): void
    {
        $expectedResponse = [
            'trace' => [
                'trace_id' => 'trace-123',
                'request_time' => 1234567890000,
                'state' => 'OK',
                'trace_location' => [
                    'experiment_id' => 'exp-456',
                ],
                'tags' => [],
            ],
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/traces/get',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);

                    return is_array($json) && $json['trace_id'] === 'trace-123';
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $trace = $this->api->getTrace('trace-123');

        $this->assertInstanceOf(Trace::class, $trace);
        $this->assertEquals('trace-123', $trace->getInfo()->getTraceId());
        $this->assertEquals(TraceState::OK, $trace->getInfo()->getState());
    }

    public function test_search_traces(): void
    {
        $expectedResponse = [
            'traces' => [
                [
                    'trace_id' => 'trace-1',
                    'request_time' => 1234567890000,
                    'state' => 'OK',
                    'trace_location' => [
                        'experiment_id' => 'exp-1',
                    ],
                ],
                [
                    'trace_id' => 'trace-2',
                    'request_time' => 1234567891000,
                    'state' => 'ERROR',
                    'trace_location' => [
                        'experiment_id' => 'exp-1',
                    ],
                ],
            ],
            'next_page_token' => 'token-abc',
        ];

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/traces/search',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);

                    return is_array($json)
                        && $json['experiment_ids'] === ['exp-1']
                        && $json['max_results'] === 100;
                })
            )
            ->willReturn($this->createJsonResponse($expectedResponse));

        $result = $this->api->searchTraces(
            experimentIds: ['exp-1'],
            maxResults: 100
        );

        $this->assertIsArray($result);
        $this->assertCount(2, $result['traces']);
        $this->assertEquals('token-abc', $result['next_page_token']);
        $this->assertInstanceOf(Trace::class, $result['traces'][0]);
        $this->assertEquals('trace-1', $result['traces'][0]->getInfo()->getTraceId());
    }

    public function test_set_trace_tag(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'mlflow/traces/set-tag',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);

                    return is_array($json)
                        && $json['trace_id'] === 'trace-123'
                        && $json['key'] === 'env'
                        && $json['value'] === 'production';
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->setTraceTag('trace-123', 'env', 'production');

        $this->assertTrue(true);
    }

    public function test_delete_trace_tag(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'DELETE',
                'mlflow/traces/delete-tag',
                $this->callback(function (array $options): bool {
                    $json = json_decode($options['body'], true);

                    return is_array($json)
                        && $json['trace_id'] === 'trace-123'
                        && $json['key'] === 'env';
                })
            )
            ->willReturn($this->createJsonResponse([]));

        $this->api->deleteTraceTag('trace-123', 'env');

        $this->assertTrue(true);
    }
}
