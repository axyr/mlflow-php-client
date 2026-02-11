<?php

declare(strict_types=1);

namespace MLflow\Api;

use MLflow\Exception\MLflowException;
use MLflow\Model\Prompt;
use MLflow\Model\PromptVersion;

/**
 * API for managing MLflow Prompt Registry
 */
class PromptApi extends BaseApi
{
    /**
     * Create a new prompt
     *
     * @param string                     $name        Prompt name
     * @param string|null                $description Prompt description
     * @param array<string, string>|null $tags        Optional tags
     *
     * @throws MLflowException
     */
    public function createPrompt(
        string $name,
        ?string $description = null,
        ?array $tags = null
    ): Prompt {
        $params = ['name' => $name];

        if ($description !== null) {
            $params['description'] = $description;
        }

        if ($tags !== null) {
            $params['tags'] = $tags;
        }

        $response = $this->post('mlflow/prompts/create', $params);

        $promptData = $response['prompt'] ?? $response;
        if (! is_array($promptData)) {
            throw new MLflowException('Invalid prompt data in response');
        }

        /** @var array<string, mixed> $promptData */
        return Prompt::fromArray($promptData);
    }

    /**
     * Create a new prompt version
     *
     * @param string                     $name           Prompt name
     * @param string                     $template       Prompt template
     * @param string|null                $description    Version description
     * @param array<string, string>|null $tags           Optional tags
     * @param array<string, mixed>|null  $responseFormat Optional response format
     * @param array<string, mixed>|null  $modelConfig    Optional model configuration
     *
     * @throws MLflowException
     */
    public function createPromptVersion(
        string $name,
        string $template,
        ?string $description = null,
        ?array $tags = null,
        ?array $responseFormat = null,
        ?array $modelConfig = null
    ): PromptVersion {
        $params = [
            'name' => $name,
            'template' => $template,
        ];

        if ($description !== null) {
            $params['description'] = $description;
        }

        if ($tags !== null) {
            $params['tags'] = $tags;
        }

        if ($responseFormat !== null) {
            $params['response_format'] = $responseFormat;
        }

        if ($modelConfig !== null) {
            $params['model_config'] = $modelConfig;
        }

        $response = $this->post('mlflow/prompts/versions/create', $params);

        $versionData = $response['prompt_version'] ?? $response;
        if (! is_array($versionData)) {
            throw new MLflowException('Invalid prompt version data in response');
        }

        /** @var array<string, mixed> $versionData */
        return PromptVersion::fromArray($versionData);
    }

    /**
     * Get a prompt by name
     *
     * @param string $name Prompt name
     *
     * @throws MLflowException
     */
    public function getPrompt(string $name): Prompt
    {
        $response = $this->get('mlflow/prompts/get', [
            'name' => $name,
        ]);

        $promptData = $response['prompt'] ?? $response;
        if (! is_array($promptData)) {
            throw new MLflowException('Invalid prompt data in response');
        }

        /** @var array<string, mixed> $promptData */
        return Prompt::fromArray($promptData);
    }

    /**
     * Get a specific prompt version
     *
     * @param string     $name    Prompt name
     * @param string|int $version Version number or name
     *
     * @throws MLflowException
     */
    public function getPromptVersion(string $name, string|int $version): PromptVersion
    {
        $response = $this->get('mlflow/prompts/versions/get', [
            'name' => $name,
            'version' => (string) $version,
        ]);

        $versionData = $response['prompt_version'] ?? $response;
        if (! is_array($versionData)) {
            throw new MLflowException('Invalid prompt version data in response');
        }

        /** @var array<string, mixed> $versionData */
        return PromptVersion::fromArray($versionData);
    }

    /**
     * Get a prompt version by alias
     *
     * @param string $name  Prompt name
     * @param string $alias Alias name
     *
     * @throws MLflowException
     */
    public function getPromptVersionByAlias(string $name, string $alias): PromptVersion
    {
        $response = $this->get('mlflow/prompts/versions/get-by-alias', [
            'name' => $name,
            'alias' => $alias,
        ]);

        $versionData = $response['prompt_version'] ?? $response;
        if (! is_array($versionData)) {
            throw new MLflowException('Invalid prompt version data in response');
        }

        /** @var array<string, mixed> $versionData */
        return PromptVersion::fromArray($versionData);
    }

    /**
     * Search prompt versions
     *
     * @param string|null        $filter     Filter string
     * @param int                $maxResults Maximum results to return
     * @param string|null        $pageToken  Page token for pagination
     * @param array<string>|null $orderBy    Order by fields
     *
     * @return array{prompt_versions: PromptVersion[], next_page_token: string|null}
     *
     * @throws MLflowException
     */
    public function searchPromptVersions(
        ?string $filter = null,
        int $maxResults = 1000,
        ?string $pageToken = null,
        ?array $orderBy = null
    ): array {
        $params = ['max_results' => $maxResults];

        if ($filter !== null) {
            $params['filter'] = $filter;
        }

        if ($pageToken !== null) {
            $params['page_token'] = $pageToken;
        }

        if ($orderBy !== null) {
            $params['order_by'] = $orderBy;
        }

        $response = $this->post('mlflow/prompts/versions/search', $params);

        $versions = [];
        if (isset($response['prompt_versions']) && is_array($response['prompt_versions'])) {
            foreach ($response['prompt_versions'] as $versionData) {
                if (is_array($versionData)) {
                    /** @var array<string, mixed> $versionData */
                    $versions[] = PromptVersion::fromArray($versionData);
                }
            }
        }

        $nextPageToken = $response['next_page_token'] ?? null;

        return [
            'prompt_versions' => $versions,
            'next_page_token' => is_string($nextPageToken) ? $nextPageToken : null,
        ];
    }

    /**
     * Delete a prompt
     *
     * @param string $name Prompt name
     *
     * @throws MLflowException
     */
    public function deletePrompt(string $name): void
    {
        $this->delete('mlflow/prompts/delete', [
            'name' => $name,
        ]);
    }

    /**
     * Delete a prompt version
     *
     * @param string     $name    Prompt name
     * @param string|int $version Version number or name
     *
     * @throws MLflowException
     */
    public function deletePromptVersion(string $name, string|int $version): void
    {
        $this->delete('mlflow/prompts/versions/delete', [
            'name' => $name,
            'version' => (string) $version,
        ]);
    }

    /**
     * Set an alias for a prompt version
     *
     * @param string     $name    Prompt name
     * @param string     $alias   Alias name
     * @param string|int $version Version number or name
     *
     * @throws MLflowException
     */
    public function setPromptAlias(string $name, string $alias, string|int $version): void
    {
        $this->post('mlflow/prompts/versions/set-alias', [
            'name' => $name,
            'alias' => $alias,
            'version' => (string) $version,
        ]);
    }

    /**
     * Delete an alias from a prompt version
     *
     * @param string $name  Prompt name
     * @param string $alias Alias name
     *
     * @throws MLflowException
     */
    public function deletePromptAlias(string $name, string $alias): void
    {
        $this->delete('mlflow/prompts/versions/delete-alias', [
            'name' => $name,
            'alias' => $alias,
        ]);
    }

    /**
     * Update a prompt
     *
     * @param string                     $name        Prompt name
     * @param string|null                $description New description
     * @param array<string, string>|null $tags        New tags
     *
     * @throws MLflowException
     */
    public function updatePrompt(
        string $name,
        ?string $description = null,
        ?array $tags = null
    ): void {
        $params = ['name' => $name];

        if ($description !== null) {
            $params['description'] = $description;
        }

        if ($tags !== null) {
            $params['tags'] = $tags;
        }

        $this->patch('mlflow/prompts/update', $params);
    }

    /**
     * Update a prompt version
     *
     * @param string                     $name        Prompt name
     * @param string|int                 $version     Version number or name
     * @param string|null                $description New description
     * @param array<string, string>|null $tags        New tags
     *
     * @throws MLflowException
     */
    public function updatePromptVersion(
        string $name,
        string|int $version,
        ?string $description = null,
        ?array $tags = null
    ): void {
        $params = [
            'name' => $name,
            'version' => (string) $version,
        ];

        if ($description !== null) {
            $params['description'] = $description;
        }

        if ($tags !== null) {
            $params['tags'] = $tags;
        }

        $this->patch('mlflow/prompts/versions/update', $params);
    }
}
