<?php

declare(strict_types=1);

namespace MLflow\Laravel\Console;

use Illuminate\Console\Command;
use MLflow\MLflowClient;

/**
 * Test connection command for MLflow server
 *
 * This command verifies that the Laravel application can successfully
 * connect to the configured MLflow tracking server.
 */
class TestConnectionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mlflow:test-connection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test connection to MLflow server';

    /**
     * Execute the console command.
     *
     * @param MLflowClient $client The MLflow client instance
     */
    public function handle(MLflowClient $client): int
    {
        $this->info('Testing connection to MLflow...');
        $uriConfig = config('mlflow.tracking_uri');
        $uri = is_string($uriConfig) ? $uriConfig : 'http://localhost:5000';
        $this->line("Server: {$uri}");

        try {
            if ($client->validateConnection()) {
                $serverInfo = $client->getServerInfo();
                $this->info('Connection successful!');
                $this->newLine();
                $this->table(['Property', 'Value'], [
                    ['Version', $serverInfo['version'] ?? 'N/A'],
                    ['Tracking URI', $uri],
                ]);

                return self::SUCCESS;
            }

            $this->error('Connection failed');

            return self::FAILURE;
        } catch (\Exception $e) {
            $this->error('Connection failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
