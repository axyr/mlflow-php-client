<?php

/**
 * Search and Compare Runs Example
 *
 * This example demonstrates:
 * - Searching experiments
 * - Searching runs with filters
 * - Comparing multiple runs
 * - Finding the best performing model
 *
 * Run: php examples/06-search-and-compare.php
 */

require __DIR__ . '/../vendor/autoload.php';
use MLflow\Enum\RunStatus;

use MLflow\MLflowClient;

$client = new MLflowClient('http://localhost:5555');

// Create experiment with multiple runs
echo "Setting up experiment with multiple runs...\n";
$experiment = $client->experiments()->create('hyperparameter-tuning');

$configs = [
    ['lr' => '0.001', 'batch_size' => '32'],
    ['lr' => '0.01', 'batch_size' => '32'],
    ['lr' => '0.001', 'batch_size' => '64'],
    ['lr' => '0.01', 'batch_size' => '64'],
];

$runIds = [];
foreach ($configs as $i => $config) {
    echo "  Creating run " . ($i + 1) . "...\n";

    $accuracy = 0.80 + (rand(0, 15) / 100);
    $loss = 0.50 - (rand(0, 20) / 100);

    $run = $client->createRunBuilder($experiment->experimentId)
        ->withName("config-{$i}")
        ->withParam('learning_rate', $config['lr'])
        ->withParam('batch_size', $config['batch_size'])
        ->withMetric('accuracy', $accuracy)
        ->withMetric('loss', $loss)
        ->withTag('config_id', (string) $i)
        ->start();

    $runIds[] = $run->info->runId;

    $client->runs()->setTerminated($run->info->runId, RunStatus::FINISHED);

    // Small delay to ensure metrics are persisted
    usleep(100000); // 100ms
}

echo "✅ Created " . count($runIds) . " runs\n\n";

// Search for all completed runs
echo "Searching for completed runs...\n";
$result = $client->runs()->search(
    experimentIds: [$experiment->experimentId],
    filter: "attributes.status = 'FINISHED'"
);

echo "Found " . count($result['runs']) . " completed runs\n\n";

// Search for runs with high accuracy
echo "Searching for runs with accuracy > 0.85...\n";
$result = $client->runs()->search(
    experimentIds: [$experiment->experimentId],
    filter: "metrics.accuracy > 0.85"
);

echo "Found " . count($result['runs']) . " high-accuracy runs:\n";
foreach ($result['runs'] as $run) {
    $latestMetrics = $run->data->metrics->getLatestByKey();
    $accuracy = $latestMetrics['accuracy']->value ?? 0;
    $loss = $latestMetrics['loss']->value ?? 0;
    $lr = $run->data->params->get('learning_rate')?->value ?? 'N/A';
    $batch = $run->data->params->get('batch_size')?->value ?? 'N/A';

    echo sprintf(
        "  Run %s: accuracy=%.3f (lr=%s, batch=%s)\n",
        substr($run->info->runId, 0, 8),
        $accuracy,
        $lr,
        $batch
    );
}
echo "\n";

// Find the best run (highest accuracy)
echo "Finding best performing run...\n";
$result = $client->runs()->search(
    experimentIds: [$experiment->experimentId],
    orderBy: ["metrics.accuracy DESC"],
    maxResults: 1
);

if (!empty($result['runs'])) {
    $bestRun = $result['runs'][0];
    $latestMetrics = $bestRun->data->metrics->getLatestByKey();
    $accuracy = $latestMetrics['accuracy']->value ?? 0;
    $loss = $latestMetrics['loss']->value ?? 0;
    $lr = $bestRun->data->params->get('learning_rate')?->value ?? 'N/A';
    $batch = $bestRun->data->params->get('batch_size')?->value ?? 'N/A';

    echo "🏆 Best run: {$bestRun->info->runId}\n";
    echo "   Accuracy: {$accuracy}\n";
    echo "   Loss: {$loss}\n";
    echo "   Learning rate: {$lr}\n";
    echo "   Batch size: {$batch}\n";
}

echo "\n🎉 Search and compare example completed!\n";
