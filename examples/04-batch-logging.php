<?php

/**
 * Batch Logging Example
 *
 * This example demonstrates efficient batch operations:
 * - Logging multiple metrics at once
 * - Logging multiple parameters at once
 * - Using batch operations for performance
 *
 * Run: php examples/04-batch-logging.php
 */

require __DIR__ . '/../vendor/autoload.php';

use MLflow\Enum\RunStatus;
use MLflow\MLflowClient;
use MLflow\Model\Metric;
use MLflow\Model\Param;
use MLflow\Model\RunTag;

$client = new MLflowClient('http://localhost:5555');

echo "Creating experiment and run...\n";
$experiment = $client->experiments()->create('batch-logging-example');
$run = $client->runs()->create($experiment->experimentId);
$runId = $run->info->runId;
echo "✅ Created run: {$runId}\n\n";

// Prepare batch parameters
echo "Preparing batch parameters...\n";
$params = [
    Param::create('learning_rate', '0.001'),
    Param::create('batch_size', '128'),
    Param::create('optimizer', 'adam'),
    Param::create('dropout', '0.5'),
    Param::create('hidden_layers', '3'),
    Param::create('activation', 'relu'),
];

// Prepare batch metrics
echo "Preparing batch metrics...\n";
$metrics = [
    Metric::now('train_accuracy', 0.92, step: 1),
    Metric::now('train_loss', 0.25, step: 1),
    Metric::now('val_accuracy', 0.88, step: 1),
    Metric::now('val_loss', 0.30, step: 1),
    Metric::now('learning_rate', 0.001, step: 1),
];

// Prepare batch tags
echo "Preparing batch tags...\n";
$tags = [
    RunTag::create('model_type', 'cnn'),
    RunTag::create('framework', 'tensorflow'),
    RunTag::create('gpu_used', 'true'),
    RunTag::create('data_version', 'v2.1'),
];

// Log everything in one batch operation
echo "Logging batch data...\n";
$startTime = microtime(true);

$client->runs()->logBatch(
    runId: $runId,
    metrics: $metrics,
    params: $params,
    tags: $tags
);

$duration = (microtime(true) - $startTime) * 1000;
echo "✅ Logged 6 params, 5 metrics, and 4 tags in {$duration}ms\n\n";

// Log multiple training steps in batches
echo "Simulating training epochs with batch logging...\n";
for ($epoch = 2; $epoch <= 10; $epoch++) {
    $epochMetrics = [
        Metric::now('train_accuracy', 0.92 + ($epoch * 0.01), step: $epoch),
        Metric::now('train_loss', 0.25 - ($epoch * 0.02), step: $epoch),
        Metric::now('val_accuracy', 0.88 + ($epoch * 0.008), step: $epoch),
        Metric::now('val_loss', 0.30 - ($epoch * 0.015), step: $epoch),
    ];

    $client->runs()->logBatch($runId, metrics: $epochMetrics);
    echo "  Epoch {$epoch} logged\n";
}

echo "\n✅ Training simulation complete\n";

// Complete the run
$client->runs()->setTerminated($runId, RunStatus::FINISHED);

echo "🎉 Batch logging example completed!\n";
echo "\n💡 Tip: Batch operations are much faster than individual API calls!\n";
