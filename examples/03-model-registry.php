<?php

/**
 * Model Registry Example
 *
 * This example demonstrates model registry operations:
 * - Registering a new model
 * - Creating model versions
 * - Transitioning versions through stages
 * - Managing model lifecycle
 *
 * Run: php examples/03-model-registry.php
 */

require __DIR__ . '/../vendor/autoload.php';

use MLflow\Enum\ModelStage;
use MLflow\MLflowClient;

$client = new MLflowClient('http://localhost:5555');

$modelName = 'recommendation-model';

// Create a registered model
echo "Creating registered model...\n";
$model = $client->modelRegistry()->createRegisteredModel(
    name: $modelName,
    description: 'Product recommendation model using collaborative filtering'
);
echo "✅ Created model: {$model->name}\n\n";

// Create an experiment and run to get artifacts
echo "Creating training run...\n";
$experiment = $client->experiments()->create('model-registry-example');
$run = $client->createRunBuilder($experiment->experimentId)
    ->withName('model-v1-training')
    ->withParam('algorithm', 'collaborative-filtering')
    ->withParam('k_factors', '50')
    ->withMetric('rmse', 0.85)
    ->withTag('model_type', 'recommendation')
    ->start();

$runId = $run->info->runId;
echo "✅ Created run: {$runId}\n\n";

// Create first model version
echo "Creating model version 1...\n";
$version1 = $client->modelRegistry()->createModelVersion(
    name: $modelName,
    source: "runs:/{$runId}/model",
    description: 'Initial production model'
);
echo "✅ Created version: {$version1->version}\n\n";

// Transition to Staging
echo "Transitioning version 1 to Staging...\n";
$client->modelRegistry()->transitionModelVersionStage(
    name: $modelName,
    version: $version1->version,
    stage: ModelStage::STAGING
);
echo "✅ Version 1 is now in Staging\n\n";

// After testing in staging, transition to Production
echo "Transitioning version 1 to Production...\n";
$client->modelRegistry()->transitionModelVersionStage(
    name: $modelName,
    version: $version1->version,
    stage: ModelStage::PRODUCTION,
    archiveExistingVersions: true
);
echo "✅ Version 1 is now in Production\n\n";

// Create a second version (improved model)
echo "Creating improved model (version 2)...\n";
$run2 = $client->createRunBuilder($experiment->experimentId)
    ->withName('model-v2-training')
    ->withParam('algorithm', 'collaborative-filtering')
    ->withParam('k_factors', '100')
    ->withMetric('rmse', 0.78)
    ->withTag('model_type', 'recommendation')
    ->start();

$version2 = $client->modelRegistry()->createModelVersion(
    name: $modelName,
    source: "runs:/{$run2->info->runId}/model",
    description: 'Improved model with better accuracy'
);
echo "✅ Created version: {$version2->version}\n\n";

// Note: Model aliases are not available in all MLflow versions
// Skipping alias setting in this example

// List all versions
echo "Listing all model versions...\n";
$latestVersions = $client->modelRegistry()->getLatestVersions($modelName);
foreach ($latestVersions as $version) {
    echo "  Version {$version->version}: {$version->currentStage->value}\n";
}

echo "\n🎉 Model registry example completed!\n";
