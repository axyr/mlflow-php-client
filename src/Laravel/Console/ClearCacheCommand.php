<?php

declare(strict_types=1);

namespace MLflow\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Clear cache command for MLflow
 *
 * This command clears the MLflow cache to force fresh data retrieval
 * from the MLflow tracking server.
 */
class ClearCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mlflow:clear-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear MLflow cache';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Clearing MLflow cache...');

        $store = config('mlflow.cache.store', config('cache.default'));
        $storeName = is_string($store) ? $store : null;

        /** @var \Illuminate\Cache\CacheManager $cacheManager */
        $cacheManager = Cache::getFacadeRoot();
        $cacheManager->store($storeName)->clear();

        $this->info('Cache cleared successfully!');

        return self::SUCCESS;
    }
}
