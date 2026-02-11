<?php

declare(strict_types=1);

namespace MLflow\Cache;

use GuzzleHttp\ClientInterface;
use MLflow\Api\ExperimentApi;
use MLflow\Model\Experiment;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Cached wrapper for ExperimentApi
 *
 * Caches read operations (getById, getByName) to reduce API calls.
 * Write operations (create, update, delete) invalidate related cache entries.
 */
final class CachedExperimentApi extends ExperimentApi
{
    private readonly CacheInterface $cache;

    private readonly int $ttl;

    public function __construct(
        ClientInterface $client,
        ?LoggerInterface $logger = null,
        ?CacheInterface $cache = null,
        int $ttl = 300,
    ) {
        parent::__construct($client, $logger);
        $this->cache = $cache ?? throw new \InvalidArgumentException('Cache is required for CachedExperimentApi');
        $this->ttl = $ttl;
    }

    /**
     * Get experiment by ID (cached)
     */
    public function getById(string $experimentId): Experiment
    {
        $cacheKey = "mlflow:experiment:id:{$experimentId}";

        $cached = $this->cache->get($cacheKey);
        if ($cached instanceof Experiment) {
            $this->logger->debug("Cache hit for experiment {$experimentId}");

            return $cached;
        }

        $this->logger->debug("Cache miss for experiment {$experimentId}");
        $experiment = parent::getById($experimentId);
        $this->cache->set($cacheKey, $experiment, $this->ttl);

        return $experiment;
    }

    /**
     * Get experiment by name (cached)
     */
    public function getByName(string $name): Experiment
    {
        $cacheKey = 'mlflow:experiment:name:' . md5($name);

        $cached = $this->cache->get($cacheKey);
        if ($cached instanceof Experiment) {
            $this->logger->debug("Cache hit for experiment '{$name}'");

            return $cached;
        }

        $this->logger->debug("Cache miss for experiment '{$name}'");
        $experiment = parent::getByName($name);
        $this->cache->set($cacheKey, $experiment, $this->ttl);

        // Also cache by ID
        $this->cache->set("mlflow:experiment:id:{$experiment->experimentId}", $experiment, $this->ttl);

        return $experiment;
    }

    /**
     * Create experiment (invalidates cache)
     */
    public function create(
        string $name,
        ?string $artifactLocation = null,
        array $tags = []
    ): Experiment {
        $experiment = parent::create($name, $artifactLocation, $tags);

        // Cache the newly created experiment
        $this->cache->set("mlflow:experiment:id:{$experiment->experimentId}", $experiment, $this->ttl);
        $this->cache->set('mlflow:experiment:name:' . md5($name), $experiment, $this->ttl);

        return $experiment;
    }

    /**
     * Update experiment (invalidates cache)
     */
    public function update(string $experimentId, ?string $newName = null): void
    {
        parent::update($experimentId, $newName);

        // Invalidate cache entries
        $this->cache->delete("mlflow:experiment:id:{$experimentId}");
    }

    /**
     * Delete experiment (invalidates cache)
     */
    public function deleteExperiment(string $experimentId): void
    {
        parent::deleteExperiment($experimentId);

        // Invalidate cache entry
        $this->cache->delete("mlflow:experiment:id:{$experimentId}");
    }

    /**
     * Restore experiment (invalidates cache)
     */
    public function restore(string $experimentId): void
    {
        parent::restore($experimentId);

        // Invalidate cache entry
        $this->cache->delete("mlflow:experiment:id:{$experimentId}");
    }

    /**
     * Set experiment tag (invalidates cache)
     */
    public function setTag(string $experimentId, string $key, string $value): void
    {
        parent::setTag($experimentId, $key, $value);

        // Invalidate cache entry
        $this->cache->delete("mlflow:experiment:id:{$experimentId}");
    }
}
