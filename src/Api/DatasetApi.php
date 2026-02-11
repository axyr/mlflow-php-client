<?php

declare(strict_types=1);

namespace MLflow\Api;

use MLflow\Exception\MLflowException;
use MLflow\Model\Dataset;

/**
 * API for managing MLflow Datasets
 */
class DatasetApi extends BaseApi
{
    /**
     * Create a new dataset
     *
     * @param string                     $name         Dataset name
     * @param string|null                $experimentId Experiment ID to associate with
     * @param array<string, string>|null $tags         Optional tags
     *
     * @throws MLflowException
     */
    public function createDataset(
        string $name,
        ?string $experimentId = null,
        ?array $tags = null
    ): Dataset {
        $params = ['name' => $name];

        if ($experimentId !== null) {
            $params['experiment_id'] = $experimentId;
        }

        if ($tags !== null) {
            $params['tags'] = $tags;
        }

        $response = $this->post('mlflow/datasets/create', $params);

        $datasetData = $response['dataset'] ?? $response;
        if (! is_array($datasetData)) {
            throw new MLflowException('Invalid dataset data in response');
        }

        /** @var array<string, mixed> $datasetData */
        return Dataset::fromArray($datasetData);
    }

    /**
     * Get a dataset by ID
     *
     * @param string $datasetId Dataset ID
     *
     * @throws MLflowException
     */
    public function getDataset(string $datasetId): Dataset
    {
        $response = $this->get('mlflow/datasets/get', [
            'dataset_id' => $datasetId,
        ]);

        $datasetData = $response['dataset'] ?? $response;
        if (! is_array($datasetData)) {
            throw new MLflowException('Invalid dataset data in response');
        }

        /** @var array<string, mixed> $datasetData */
        return Dataset::fromArray($datasetData);
    }

    /**
     * Add a dataset to experiments
     *
     * @param string        $datasetId     Dataset ID
     * @param array<string> $experimentIds Experiment IDs to add dataset to
     *
     * @throws MLflowException
     */
    public function addDatasetToExperiments(string $datasetId, array $experimentIds): void
    {
        $this->post('mlflow/datasets/add-to-experiments', [
            'dataset_id' => $datasetId,
            'experiment_ids' => $experimentIds,
        ]);
    }

    /**
     * Search datasets
     *
     * @param string|null $experimentId Filter by experiment ID
     * @param string|null $filter       Filter string
     * @param int         $maxResults   Maximum results to return
     * @param string|null $pageToken    Page token for pagination
     *
     * @return array{datasets: Dataset[], next_page_token: string|null}
     *
     * @throws MLflowException
     */
    public function searchDatasets(
        ?string $experimentId = null,
        ?string $filter = null,
        int $maxResults = 1000,
        ?string $pageToken = null
    ): array {
        $params = ['max_results' => $maxResults];

        if ($experimentId !== null) {
            $params['experiment_id'] = $experimentId;
        }

        if ($filter !== null) {
            $params['filter'] = $filter;
        }

        if ($pageToken !== null) {
            $params['page_token'] = $pageToken;
        }

        $response = $this->post('mlflow/datasets/search', $params);

        $datasets = [];
        if (isset($response['datasets']) && is_array($response['datasets'])) {
            foreach ($response['datasets'] as $datasetData) {
                if (is_array($datasetData)) {
                    /** @var array<string, mixed> $datasetData */
                    $datasets[] = Dataset::fromArray($datasetData);
                }
            }
        }

        $nextPageToken = $response['next_page_token'] ?? null;

        return [
            'datasets' => $datasets,
            'next_page_token' => is_string($nextPageToken) ? $nextPageToken : null,
        ];
    }

    /**
     * Delete a dataset
     *
     * @param string $datasetId Dataset ID
     *
     * @throws MLflowException
     */
    public function deleteDataset(string $datasetId): void
    {
        $this->delete('mlflow/datasets/delete', [
            'dataset_id' => $datasetId,
        ]);
    }
}
