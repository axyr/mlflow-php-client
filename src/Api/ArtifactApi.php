<?php

declare(strict_types=1);

namespace MLflow\Api;

use GuzzleHttp\Psr7;
use MLflow\Contracts\ArtifactApiContract;
use MLflow\Exception\MLflowException;
use MLflow\Model\FileInfo;

/**
 * Complete API for managing MLflow artifacts
 * Implements all REST API endpoints from MLflow official documentation
 */
class ArtifactApi extends BaseApi implements ArtifactApiContract
{
    /**
     * List artifacts for a run
     *
     * @param string      $runId The run ID
     * @param string|null $path  Optional path within the artifact directory
     *
     * @return array{root_uri: string|null, files: FileInfo[]} Array with root_uri and FileInfo objects
     *
     * @throws MLflowException
     */
    public function list(string $runId, ?string $path = null): array
    {
        $query = ['run_id' => $runId];

        if ($path !== null) {
            $query['path'] = $path;
        }

        $response = $this->get('mlflow/artifacts/list', $query);

        $files = [];
        if (isset($response['files']) && is_array($response['files'])) {
            foreach ($response['files'] as $fileData) {
                if (is_array($fileData) && isset($fileData['path'])) {
                    /** @var array{path: string, is_dir?: bool, file_size?: int|null} $fileData */
                    $files[] = FileInfo::fromArray($fileData);
                }
            }
        }

        $rootUri = $response['root_uri'] ?? null;

        return [
            'root_uri' => is_string($rootUri) ? $rootUri : null,
            'files' => $files,
        ];
    }

    /**
     * Log a single artifact file for a run
     *
     * @param string      $runId        The run ID
     * @param string      $localPath    Path to the local file
     * @param string|null $artifactPath Optional path within the artifact directory
     *
     * @throws MLflowException
     */
    public function logArtifact(string $runId, string $localPath, ?string $artifactPath = null): void
    {
        if (! file_exists($localPath)) {
            throw new MLflowException("File not found: $localPath");
        }

        if (! is_file($localPath)) {
            throw new MLflowException("Path is not a file: $localPath");
        }

        $filename = basename($localPath);
        $fullArtifactPath = $artifactPath ? "$artifactPath/$filename" : $filename;

        // Get artifact store URI
        $runInfo = $this->getRunInfo($runId);
        $artifactUri = $runInfo['artifact_uri'] ?? null;

        if (! is_string($artifactUri) || $artifactUri === '') {
            throw new MLflowException("Could not get artifact URI for run $runId");
        }

        // For file store, directly copy the file
        if (str_starts_with($artifactUri, 'file://')) {
            $this->copyToFileStore($localPath, $artifactUri, $fullArtifactPath);

            return;
        }

        // For other stores, use multipart upload if proxying is enabled
        $this->uploadViaProxy($runId, $localPath, $fullArtifactPath);
    }

    /**
     * Log multiple artifacts from a directory
     *
     * @param string      $runId        The run ID
     * @param string      $localDir     Path to the local directory
     * @param string|null $artifactPath Optional path within the artifact directory
     *
     * @throws MLflowException
     */
    public function logArtifacts(string $runId, string $localDir, ?string $artifactPath = null): void
    {
        if (! is_dir($localDir)) {
            throw new MLflowException("Directory not found: $localDir");
        }

        $files = $this->scanDirectory($localDir);
        $basePath = realpath($localDir);

        if ($basePath === false) {
            throw new MLflowException("Could not resolve path: $localDir");
        }

        foreach ($files as $file) {
            $relativePath = str_replace([$basePath . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], ['', '/'], $file);

            $targetPath = $artifactPath ? "$artifactPath/$relativePath" : $relativePath;
            $targetDir = dirname($targetPath);

            if ($targetDir !== '.') {
                $this->logArtifact($runId, $file, $targetDir);
            } else {
                $this->logArtifact($runId, $file, $artifactPath);
            }
        }
    }

    /**
     * Download artifacts for a run
     *
     * @param string      $runId        The run ID
     * @param string|null $artifactPath Path within the artifact directory
     * @param string      $dstPath      Local destination path
     *
     * @return string The path to downloaded artifacts
     *
     * @throws MLflowException
     */
    public function download(string $runId, ?string $artifactPath, string $dstPath): string
    {
        // Get artifact URI
        $artifacts = $this->list($runId, $artifactPath);
        $rootUri = $artifacts['root_uri'] ?? null;

        if (! $rootUri) {
            throw new MLflowException("Could not get artifact URI for run $runId");
        }

        // Create destination directory if it doesn't exist
        if (! is_dir($dstPath)) {
            if (! mkdir($dstPath, 0777, true)) {
                throw new MLflowException("Failed to create directory: $dstPath");
            }
        }

        // Download each file
        foreach ($artifacts['files'] as $fileInfo) {
            $this->downloadFile($runId, $fileInfo, $dstPath, $artifactPath);
        }

        return $dstPath;
    }

    /**
     * Get download URI for artifacts
     *
     * @param string      $runId        The run ID
     * @param string|null $artifactPath Path within the artifact directory
     *
     * @return string The download URI
     *
     * @throws MLflowException
     */
    public function getDownloadUri(string $runId, ?string $artifactPath = null): string
    {
        $query = ['run_id' => $runId];

        if ($artifactPath !== null) {
            $query['path'] = $artifactPath;
        }

        $response = $this->get('mlflow/artifacts/get-artifact', $query);

        $uri = $response['artifact_uri'] ?? '';

        return is_string($uri) ? $uri : '';
    }

    /**
     * Upload artifact via proxy (when MLFLOW_ENABLE_PROXY_MULTIPART_UPLOAD is enabled)
     *
     * @param string $runId        The run ID
     * @param string $localPath    Path to local file
     * @param string $artifactPath Path within the artifact directory
     *
     * @throws MLflowException
     */
    private function uploadViaProxy(string $runId, string $localPath, string $artifactPath): void
    {
        try {
            $fileStream = Psr7\Utils::tryFopen($localPath, 'r');

            $multipart = [
                [
                    'name' => 'file',
                    'contents' => $fileStream,
                    'filename' => basename($localPath),
                ],
            ];

            $this->httpClient->request('POST', "mlflow-artifacts/artifacts/{$runId}/{$artifactPath}", [
                'multipart' => $multipart,
            ]);
        } catch (\Exception $e) {
            throw new MLflowException(
                'Failed to upload artifact: ' . $e->getMessage(),
                0,
                null,
                $e
            );
        }
    }

    /**
     * Copy file to file store
     *
     * @param string $localPath    Source file path
     * @param string $artifactUri  Artifact store URI
     * @param string $artifactPath Target path within artifacts
     *
     * @throws MLflowException
     */
    private function copyToFileStore(string $localPath, string $artifactUri, string $artifactPath): void
    {
        $targetPath = str_replace('file://', '', $artifactUri) . '/' . $artifactPath;
        $targetDir = dirname($targetPath);

        if (! is_dir($targetDir)) {
            if (! mkdir($targetDir, 0777, true)) {
                throw new MLflowException("Failed to create artifact directory: $targetDir");
            }
        }

        if (! copy($localPath, $targetPath)) {
            throw new MLflowException("Failed to copy file to artifact store: $targetPath");
        }
    }

    /**
     * Scan directory recursively for files
     *
     * @param string $dir Directory to scan
     *
     * @return string[] Array of file paths
     */
    private function scanDirectory(string $dir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item instanceof \SplFileInfo && $item->isFile()) {
                $files[] = $item->getPathname();
            }
        }

        return $files;
    }

    /**
     * Download a single file
     *
     * @param string      $runId        The run ID
     * @param FileInfo    $fileInfo     File information
     * @param string      $dstPath      Destination directory
     * @param string|null $artifactPath Artifact path
     *
     * @throws MLflowException
     */
    private function downloadFile(
        string $runId,
        FileInfo $fileInfo,
        string $dstPath,
        ?string $artifactPath
    ): void {
        $filePath = $fileInfo->path;
        $localPath = $dstPath . '/' . basename($filePath);

        if ($fileInfo->isDir) {
            // Recursively download directory contents
            $this->download($runId, $filePath, $localPath);

            return;
        }

        // Download the file
        try {
            $response = $this->httpClient->request(
                'GET',
                "mlflow-artifacts/artifacts/{$runId}/{$filePath}"
            );

            $fileHandle = fopen($localPath, 'wb');
            if (! $fileHandle) {
                throw new MLflowException("Failed to create file: $localPath");
            }

            fwrite($fileHandle, $response->getBody()->getContents());
            fclose($fileHandle);
        } catch (\Exception $e) {
            throw new MLflowException(
                'Failed to download artifact: ' . $e->getMessage(),
                0,
                null,
                $e
            );
        }
    }

    /**
     * Get run info to retrieve artifact URI
     *
     * @param string $runId The run ID
     *
     * @return array<string, mixed> Run information
     *
     * @throws MLflowException
     */
    private function getRunInfo(string $runId): array
    {
        $response = $this->get('mlflow/runs/get', ['run_id' => $runId]);
        $run = $response['run'] ?? null;
        if (! is_array($run)) {
            return [];
        }
        $info = $run['info'] ?? [];

        return is_array($info) ? $info : [];
    }
}
