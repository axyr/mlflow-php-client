<?php

declare(strict_types=1);

namespace MLflow\Cache;

use MLflow\Api\ExperimentApi;
use MLflow\Config\MLflowConfig;
use MLflow\MLflowClient;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * MLflow client with response caching support
 *
 * Wraps the standard MLflowClient to provide PSR-16 caching for read operations.
 * This can significantly improve performance for frequently accessed data like experiments.
 *
 * Example:
 * ```php
 * use Symfony\Component\Cache\Psr16Cache;
 * use Symfony\Component\Cache\Adapter\FilesystemAdapter;
 *
 * $cache = new Psr16Cache(new FilesystemAdapter());
 * $client = new CachingMLflowClient(
 *     'http://localhost:5000',
 *     cache: $cache,
 *     cacheTtl: 300 // 5 minutes
 * );
 *
 * // First call hits MLflow server
 * $exp = $client->experiments()->getById('123');
 *
 * // Second call uses cached response
 * $exp = $client->experiments()->getById('123');
 * ```
 */
final class CachingMLflowClient extends MLflowClient
{
    public function __construct(
        string $trackingUri,
        MLflowConfig|array $config = [],
        ?LoggerInterface $logger = null,
        private readonly ?CacheInterface $cache = null,
        private readonly int $cacheTtl = 300,
    ) {
        parent::__construct($trackingUri, $config, $logger);
    }

    /**
     * Get experiment API with caching support
     */
    public function experiments(): ExperimentApi
    {
        if ($this->cache !== null) {
            return new CachedExperimentApi(
                $this->getHttpClient(),
                $this->logger,
                $this->cache,
                $this->cacheTtl
            );
        }

        return parent::experiments();
    }
}
