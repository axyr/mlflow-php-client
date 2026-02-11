<?php

declare(strict_types=1);

namespace MLflow\Laravel\Console;

use Illuminate\Console\Command;

class GenerateIdeHelperCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mlflow:ide-helper';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate IDE helper file for MLflow facade';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating IDE helper for MLflow...');

        $content = $this->generateHelperContent();

        $path = base_path('_ide_helper_mlflow.php');

        file_put_contents($path, $content);

        $this->info("IDE helper generated at: {$path}");
        $this->comment('Add this file to your .gitignore if desired, or commit it for team-wide IDE support.');

        return Command::SUCCESS;
    }

    /**
     * Generate the IDE helper file content
     */
    private function generateHelperContent(): string
    {
        return <<<'PHP'
<?php

/**
 * IDE Helper for MLflow Laravel Integration
 *
 * This file provides IDE autocomplete support for the MLflow facade and testing utilities.
 * It is automatically generated and should not be edited manually.
 *
 * @see https://github.com/barryvdh/laravel-ide-helper
 */

namespace Illuminate\Support\Facades {

    /**
     * MLflow Facade
     *
     * @method static \MLflow\Api\ExperimentApi experiments()
     * @method static \MLflow\Api\RunApi runs()
     * @method static \MLflow\Api\ModelRegistryApi modelRegistry()
     * @method static \MLflow\Api\ModelRegistryApi models()
     * @method static \MLflow\Api\MetricApi metrics()
     * @method static \MLflow\Api\ArtifactApi artifacts()
     * @method static \MLflow\Api\TraceApi traces()
     * @method static \MLflow\Api\PromptApi prompts()
     * @method static \MLflow\Api\WebhookApi webhooks()
     * @method static \MLflow\Api\DatasetApi datasets()
     * @method static \MLflow\Builder\RunBuilder createRunBuilder(string $experimentId)
     * @method static \MLflow\Builder\ExperimentBuilder createExperimentBuilder(string $name)
     * @method static \MLflow\Builder\ModelBuilder createModelBuilder(string $name)
     * @method static \MLflow\Builder\TraceBuilder createTraceBuilder(string $experimentId, string $name)
     * @method static bool validateConnection()
     * @method static array getServerInfo()
     * @method static \MLflow\Testing\Fakes\MLflowFake fake()
     * @method static void assertExperimentCreated(string $name)
     * @method static void assertRunStarted(string $experimentId)
     * @method static void assertMetricLogged(string $runId, string $key, float|null $value = null)
     * @method static void assertParamLogged(string $runId, string $key, string|null $value = null)
     * @method static void assertTagSet(string $runId, string $key, string|null $value = null)
     * @method static void assertModelRegistered(string $name)
     * @method static void assertNothingLogged()
     * @method static array getRecordedExperiments()
     * @method static array getRecordedRuns()
     * @method static array getRecordedMetrics()
     * @method static array getRecordedParams()
     * @method static array getRecordedTags()
     * @method static array getRecordedModels()
     * @method static int getExperimentCount()
     * @method static int getRunCount()
     * @method static int getMetricCount()
     * @method static int getParamCount()
     * @method static int getModelCount()
     * @method static void reset()
     *
     * @see \MLflow\Laravel\Facades\MLflow
     * @see \MLflow\MLflowClient
     */
    class MLflow extends Facade
    {
    }
}

namespace {

    /**
     * Get the MLflow client instance
     *
     * @return \MLflow\MLflowClient
     */
    function mlflow(): \MLflow\MLflowClient
    {
    }
}

PHP;
    }
}
