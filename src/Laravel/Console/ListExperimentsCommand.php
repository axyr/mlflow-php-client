<?php

declare(strict_types=1);

namespace MLflow\Laravel\Console;

use Illuminate\Console\Command;
use MLflow\MLflowClient;

/**
 * List experiments command
 *
 * This command retrieves and displays a list of MLflow experiments
 * with support for filtering and pagination.
 */
class ListExperimentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mlflow:experiments:list
                            {--filter= : Filter string}
                            {--max=50 : Maximum results}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List MLflow experiments';

    /**
     * Execute the console command.
     *
     * @param MLflowClient $client The MLflow client instance
     */
    public function handle(MLflowClient $client): int
    {
        $filterOption = $this->option('filter');
        $filter = is_string($filterOption) ? $filterOption : null;
        $max = (int) $this->option('max');

        $this->info('Fetching experiments...');

        $result = $client->experiments()->search(
            filterString: $filter,
            maxResults: $max
        );

        if (empty($result['experiments'])) {
            $this->warn('No experiments found');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($result['experiments'] as $exp) {
            $rows[] = [
                $exp->experimentId,
                $exp->name,
                $exp->lifecycleStage,
                $exp->artifactLocation ?? 'N/A',
            ];
        }

        $this->table(
            ['ID', 'Name', 'Status', 'Artifact Location'],
            $rows
        );

        $this->info('Total: ' . count($result['experiments']));

        return self::SUCCESS;
    }
}
