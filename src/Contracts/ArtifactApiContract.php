<?php

declare(strict_types=1);

namespace MLflow\Contracts;

use MLflow\Model\FileInfo;

/**
 * Contract for Artifact API
 */
interface ArtifactApiContract
{
    /**
     * List artifacts for a run
     *
     * @param string      $runId The run ID
     * @param string|null $path  Optional path within the artifact directory
     *
     * @return array{root_uri: string|null, files: array<FileInfo>}
     */
    public function list(string $runId, ?string $path = null): array;

    /**
     * Log a single artifact file for a run
     *
     * @param string      $runId        The run ID
     * @param string      $localPath    Path to the local file
     * @param string|null $artifactPath Optional path within the artifact directory
     */
    public function logArtifact(string $runId, string $localPath, ?string $artifactPath = null): void;

    /**
     * Download artifacts for a run
     *
     * @param string      $runId        The run ID
     * @param string|null $artifactPath Path within the artifact directory
     * @param string      $dstPath      Local destination path
     *
     * @return string The path to downloaded artifacts
     */
    public function download(string $runId, ?string $artifactPath, string $dstPath): string;
}
