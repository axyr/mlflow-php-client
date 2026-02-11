<?php

declare(strict_types=1);

namespace MLflow\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Install command for MLflow Laravel Package
 *
 * This command handles the initial setup of the MLflow package in a Laravel application.
 * It publishes configuration files and adds necessary environment variables.
 */
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mlflow:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure the MLflow package';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing MLflow Laravel Package...');

        // Publish config
        $this->call('vendor:publish', ['--tag' => 'mlflow-config', '--force' => false]);

        // Check if .env has MLflow config
        if (! $this->envHasKey('MLFLOW_TRACKING_URI')) {
            $this->updateEnvFile();
        }

        $this->info('MLflow package installed successfully!');
        $this->newLine();
        $this->info('Next steps:');
        $this->line('1. Update MLFLOW_TRACKING_URI in your .env file');
        $this->line('2. Run: php artisan mlflow:test-connection');

        return self::SUCCESS;
    }

    /**
     * Check if environment file has the specified key
     *
     * @param string $key The environment key to check
     */
    private function envHasKey(string $key): bool
    {
        $envFile = base_path('.env');
        if (! File::exists($envFile)) {
            return false;
        }

        return str_contains(File::get($envFile), $key);
    }

    /**
     * Update the environment file with MLflow configuration
     */
    private function updateEnvFile(): void
    {
        $envFile = base_path('.env');
        $envContent = File::get($envFile);

        $mlflowConfig = <<<'ENV'

# MLflow Configuration
MLFLOW_TRACKING_URI=http://localhost:5000
MLFLOW_API_TOKEN=
MLFLOW_DEFAULT_EXPERIMENT=
MLFLOW_LOGGING_ENABLED=false
MLFLOW_CACHE_ENABLED=false
ENV;

        File::put($envFile, $envContent . $mlflowConfig);
        $this->info('Added MLflow configuration to .env file');
    }
}
