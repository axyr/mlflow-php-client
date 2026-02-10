<?php

declare(strict_types=1);

namespace MLflow\Api;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use MLflow\Exception\MLflowException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Base API class providing common functionality for all API endpoints
 */
abstract class BaseApi
{
    protected ClientInterface $httpClient;
    protected LoggerInterface $logger;

    public function __construct(ClientInterface $httpClient, ?LoggerInterface $logger = null)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Make a GET request
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $query Query parameters
     * @return array<string, mixed> The response data
     * @throws MLflowException
     */
    protected function get(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $endpoint, ['query' => $query]);
    }

    /**
     * Make a POST request
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $data Request body data
     * @return array<string, mixed> The response data
     * @throws MLflowException
     */
    protected function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, ['json' => $data]);
    }

    /**
     * Make a PATCH request
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $data Request body data
     * @return array<string, mixed> The response data
     * @throws MLflowException
     */
    protected function patch(string $endpoint, array $data = []): array
    {
        return $this->request('PATCH', $endpoint, ['json' => $data]);
    }

    /**
     * Make a DELETE request
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $data Request body data
     * @return array<string, mixed> The response data
     * @throws MLflowException
     */
    protected function delete(string $endpoint, array $data = []): array
    {
        return $this->request('DELETE', $endpoint, ['json' => $data]);
    }

    /**
     * Make an HTTP request
     *
     * @param string $method HTTP method
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $options Request options
     * @return array<string, mixed> The response data
     * @throws MLflowException
     */
    protected function request(string $method, string $endpoint, array $options = []): array
    {
        try {
            // Convert 'json' option to proper request format with body
            if (isset($options['json'])) {
                $options['body'] = json_encode($options['json']);
                $options['headers'] = array_merge(
                    $options['headers'] ?? [],
                    ['Content-Type' => 'application/json']
                );
                unset($options['json']);
            }

            $this->logger->debug('Making API request', [
                'method' => $method,
                'endpoint' => $endpoint,
                'options' => $options,
            ]);

            $response = $this->httpClient->request($method, $endpoint, $options);

            return $this->parseResponse($response);
        } catch (RequestException $e) {
            $this->handleRequestException($e);
        } catch (GuzzleException $e) {
            throw new MLflowException(
                'Request failed: ' . $e->getMessage(),
                $e->getCode(),
                null,
                $e
            );
        } catch (\Exception $e) {
            throw new MLflowException(
                'Request failed: ' . $e->getMessage(),
                0,
                null,
                $e
            );
        }
    }

    /**
     * Parse the response
     *
     * @param ResponseInterface $response
     * @return array<string, mixed>
     * @throws MLflowException
     */
    private function parseResponse(ResponseInterface $response): array
    {
        $contents = $response->getBody()->getContents();

        if (empty($contents)) {
            return [];
        }

        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            throw new MLflowException('Invalid response format: expected array, got ' . gettype($data));
        }

        return $data;
    }

    /**
     * Handle request exceptions
     *
     * @param RequestException $e
     * @throws MLflowException
     */
    private function handleRequestException(RequestException $e): void
    {
        $response = $e->getResponse();

        if ($response === null) {
            throw new MLflowException(
                'Network error: ' . $e->getMessage(),
                0,
                null,
                $e
            );
        }

        $statusCode = $response->getStatusCode();
        $body = null;

        try {
            $contents = $response->getBody()->getContents();
            if (!empty($contents)) {
                $body = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            }
        } catch (\Exception $parseException) {
            $this->logger->warning('Failed to parse error response', [
                'exception' => $parseException->getMessage(),
            ]);
        }

        throw MLflowException::fromHttpError($statusCode, $e->getMessage(), $body);
    }
}