<?php

declare(strict_types=1);

namespace MLflow\Api;

use MLflow\Model\Webhook;
use MLflow\Exception\MLflowException;

/**
 * API for managing MLflow Webhooks
 */
class WebhookApi extends BaseApi
{
    /**
     * Create a new webhook
     *
     * @param string $name Webhook name
     * @param string $url Webhook URL endpoint
     * @param array<string> $events Events to trigger the webhook
     * @param string|null $description Webhook description
     * @param string|null $secret Secret for webhook authentication
     * @param string|null $status Webhook status (ACTIVE, INACTIVE)
     * @return Webhook
     * @throws MLflowException
     */
    public function createWebhook(
        string $name,
        string $url,
        array $events,
        ?string $description = null,
        ?string $secret = null,
        ?string $status = null
    ): Webhook {
        $params = [
            'name' => $name,
            'url' => $url,
            'events' => $events,
        ];

        if ($description !== null) {
            $params['description'] = $description;
        }

        if ($secret !== null) {
            $params['secret'] = $secret;
        }

        if ($status !== null) {
            $params['status'] = $status;
        }

        $response = $this->post('mlflow/registry-webhooks/create', $params);

        $webhookData = $response['webhook'] ?? $response;
        if (!is_array($webhookData)) {
            throw new MLflowException('Invalid webhook data in response');
        }

        return Webhook::fromArray($webhookData);
    }

    /**
     * List all webhooks
     *
     * @param int|null $maxResults Maximum results to return
     * @param string|null $pageToken Page token for pagination
     * @return array{webhooks: Webhook[], next_page_token: string|null}
     * @throws MLflowException
     */
    public function listWebhooks(
        ?int $maxResults = null,
        ?string $pageToken = null
    ): array {
        $params = [];

        if ($maxResults !== null) {
            $params['max_results'] = $maxResults;
        }

        if ($pageToken !== null) {
            $params['page_token'] = $pageToken;
        }

        $response = $this->get('mlflow/registry-webhooks/list', $params);

        $webhooks = [];
        if (isset($response['webhooks']) && is_array($response['webhooks'])) {
            foreach ($response['webhooks'] as $webhookData) {
                if (is_array($webhookData)) {
                    $webhooks[] = Webhook::fromArray($webhookData);
                }
            }
        }

        $nextPageToken = $response['next_page_token'] ?? null;
        return [
            'webhooks' => $webhooks,
            'next_page_token' => is_string($nextPageToken) ? $nextPageToken : null,
        ];
    }

    /**
     * Get a webhook by ID
     *
     * @param string $webhookId Webhook ID
     * @return Webhook
     * @throws MLflowException
     */
    public function getWebhook(string $webhookId): Webhook
    {
        $response = $this->get('mlflow/registry-webhooks/get', [
            'id' => $webhookId,
        ]);

        $webhookData = $response['webhook'] ?? $response;
        if (!is_array($webhookData)) {
            throw new MLflowException('Invalid webhook data in response');
        }

        return Webhook::fromArray($webhookData);
    }

    /**
     * Delete a webhook
     *
     * @param string $webhookId Webhook ID
     * @return void
     * @throws MLflowException
     */
    public function deleteWebhook(string $webhookId): void
    {
        $this->delete('mlflow/registry-webhooks/delete', [
            'id' => $webhookId,
        ]);
    }

    /**
     * Update a webhook
     *
     * @param string $webhookId Webhook ID
     * @param string|null $name New name
     * @param string|null $description New description
     * @param string|null $status New status (ACTIVE, INACTIVE)
     * @param array<string>|null $events New events
     * @return void
     * @throws MLflowException
     */
    public function updateWebhook(
        string $webhookId,
        ?string $name = null,
        ?string $description = null,
        ?string $status = null,
        ?array $events = null
    ): void {
        $params = ['id' => $webhookId];

        if ($name !== null) {
            $params['name'] = $name;
        }

        if ($description !== null) {
            $params['description'] = $description;
        }

        if ($status !== null) {
            $params['status'] = $status;
        }

        if ($events !== null) {
            $params['events'] = $events;
        }

        $this->patch('mlflow/registry-webhooks/update', $params);
    }

    /**
     * Test a webhook
     *
     * @param string $webhookId Webhook ID
     * @return array<string, mixed> Test response
     * @throws MLflowException
     */
    public function testWebhook(string $webhookId): array
    {
        $response = $this->post('mlflow/registry-webhooks/test', [
            'id' => $webhookId,
        ]);

        return is_array($response) ? $response : [];
    }
}
