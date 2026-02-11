<?php

/**
 * Fluent Builder Example
 *
 * This example demonstrates the fluent builder API for creating runs:
 * - Using RunBuilder for cleaner code
 * - Chaining methods
 * - Starting runs with all configuration in one go
 *
 * Run: php examples/02-fluent-builder.php
 */

require __DIR__ . '/../vendor/autoload.php';

use MLflow\MLflowClient;

$client = new MLflowClient('http://localhost:5555');

echo "Creating experiment...\n";
$experiment = $client->experiments()->create('fluent-builder-example');
echo "✅ Created: {$experiment->name}\n\n";

// Use the fluent builder to create and configure a run
echo "Creating run with fluent builder...\n";
$run = $client->createRunBuilder($experiment->experimentId)
    ->withName('training-run-001')
    // Parameters
    ->withParam('learning_rate', '0.001')
    ->withParam('batch_size', '64')
    ->withParam('optimizer', 'adam')
    ->withParam('epochs', '20')
    // Initial metrics
    ->withMetric('accuracy', 0.85, step: 1)
    ->withMetric('loss', 0.45, step: 1)
    ->withMetric('val_accuracy', 0.82, step: 1)
    ->withMetric('val_loss', 0.50, step: 1)
    // Tags
    ->withTag('model_type', 'transformer')
    ->withTag('framework', 'pytorch')
    ->withTag('dataset', 'imagenet')
    ->withTag('environment', 'production')
    // Start the run
    ->start();

echo "✅ Run created: {$run->info->runId}\n\n";

// Continue logging more metrics
echo "Logging additional metrics...\n";
for ($step = 2; $step <= 5; $step++) {
    $accuracy = 0.85 + ($step * 0.02);
    $loss = 0.45 - ($step * 0.05);

    $client->runs()->logMetric($run->info->runId, 'accuracy', $accuracy, step: $step);
    $client->runs()->logMetric($run->info->runId, 'loss', $loss, step: $step);

    echo "  Step {$step}: accuracy={$accuracy}, loss={$loss}\n";
}

echo "\n✅ Completed training run\n";
echo "🎉 Fluent builder example completed!\n";
