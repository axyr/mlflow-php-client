<?php

/**
 * Basic MLflow Usage Example
 *
 * This example demonstrates the fundamental operations:
 * - Creating an experiment
 * - Creating a run
 * - Logging parameters and metrics
 * - Completing the run
 *
 * Run: php examples/01-basic-usage.php
 */

require __DIR__ . '/../vendor/autoload.php';
use MLflow\Enum\RunStatus;
use MLflow\MLflowClient;

// Initialize the MLflow client
$client = new MLflowClient('http://localhost:5555');

// Verify connection
echo "Connecting to MLflow...\n";
if (! $client->validateConnection()) {
    exit("❌ Could not connect to MLflow server\n");
}
echo "✅ Connected to MLflow\n\n";

// Create an experiment
echo "Creating experiment...\n";
$experiment = $client->experiments()->create('basic-example');
echo "✅ Created experiment: {$experiment->name} (ID: {$experiment->experimentId})\n\n";

// Create a run
echo "Creating run...\n";
$run = $client->runs()->create($experiment->experimentId);
$runId = $run->info->runId;
echo "✅ Created run: {$runId}\n\n";

// Log parameters
echo "Logging parameters...\n";
$client->runs()->logParameter($runId, 'learning_rate', '0.01');
$client->runs()->logParameter($runId, 'batch_size', '32');
$client->runs()->logParameter($runId, 'epochs', '10');
echo "✅ Logged 3 parameters\n\n";

// Log metrics
echo "Logging metrics...\n";
$client->runs()->logMetric($runId, 'accuracy', 0.95, step: 1);
$client->runs()->logMetric($runId, 'loss', 0.05, step: 1);
$client->runs()->logMetric($runId, 'accuracy', 0.97, step: 2);
$client->runs()->logMetric($runId, 'loss', 0.03, step: 2);
echo "✅ Logged 4 metrics\n\n";

// Log tags
echo "Logging tags...\n";
$client->runs()->setTag($runId, 'model_type', 'neural_network');
$client->runs()->setTag($runId, 'framework', 'tensorflow');
echo "✅ Logged 2 tags\n\n";

// Complete the run
echo "Completing run...\n";
$client->runs()->setTerminated($runId, RunStatus::FINISHED);
echo "✅ Run completed\n\n";

// Retrieve and display the run
echo "Retrieving run details...\n";
$run = $client->runs()->getById($runId);
echo "Run ID: {$run->info->runId}\n";
echo "Status: {$run->info->status->value}\n";
echo 'Start time: ' . date('Y-m-d H:i:s', (int) ($run->info->startTime / 1000)) . "\n";

echo "\n🎉 Example completed successfully!\n";
