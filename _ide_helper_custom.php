<?php

/**
 * Custom IDE Helper for MLflow
 *
 * This file provides additional IDE autocomplete support beyond the auto-generated files.
 * Commit this file to your repository for team-wide IDE support.
 */

namespace PHPSTORM_META {

    // Override for MLflow facade methods to ensure proper return types
    override(\MLflow\Laravel\Facades\MLflow::fake(), type(0));

    // Experiments API autocomplete
    expectedArguments(
        \MLflow\Api\ExperimentApi::search(),
        4,
        \MLflow\Enum\ViewType::ACTIVE_ONLY,
        \MLflow\Enum\ViewType::DELETED_ONLY,
        \MLflow\Enum\ViewType::ALL
    );

    // Runs API autocomplete
    expectedArguments(
        \MLflow\Api\RunApi::search(),
        2,
        \MLflow\Enum\ViewType::ACTIVE_ONLY,
        \MLflow\Enum\ViewType::DELETED_ONLY,
        \MLflow\Enum\ViewType::ALL
    );

    expectedArguments(
        \MLflow\Api\RunApi::setTerminated(),
        1,
        \MLflow\Enum\RunStatus::FINISHED,
        \MLflow\Enum\RunStatus::FAILED,
        \MLflow\Enum\RunStatus::KILLED
    );

    expectedArguments(
        \MLflow\Api\RunApi::update(),
        1,
        \MLflow\Enum\RunStatus::FINISHED,
        \MLflow\Enum\RunStatus::FAILED,
        \MLflow\Enum\RunStatus::KILLED,
        \MLflow\Enum\RunStatus::RUNNING,
        \MLflow\Enum\RunStatus::SCHEDULED
    );

    // Model Registry API autocomplete
    expectedArguments(
        \MLflow\Api\ModelRegistryApi::transitionModelVersionStage(),
        2,
        \MLflow\Enum\ModelStage::STAGING,
        \MLflow\Enum\ModelStage::PRODUCTION,
        \MLflow\Enum\ModelStage::ARCHIVED,
        \MLflow\Enum\ModelStage::NONE
    );

    // Builder patterns return type
    override(\MLflow\Builder\RunBuilder::start(), type(0));
    override(\MLflow\Builder\RunBuilder::create(), type(0));
    override(\MLflow\Builder\ExperimentBuilder::create(), type(0));
    override(\MLflow\Builder\ModelBuilder::create(), type(0));
    override(\MLflow\Builder\TraceBuilder::build(), type(0));

    // Helper function return type
    override(\mlflow(), type(0));

    // Contract bindings for dependency injection
    registerArgumentsSet(
        'mlflow_contracts',
        \MLflow\Contracts\MLflowClientContract::class,
        \MLflow\Contracts\ExperimentApiContract::class,
        \MLflow\Contracts\RunApiContract::class,
        \MLflow\Contracts\ModelRegistryApiContract::class,
        \MLflow\Contracts\MetricApiContract::class,
        \MLflow\Contracts\ArtifactApiContract::class
    );

    expectedArguments(
        \Illuminate\Container\Container::make(),
        0,
        argumentsSet('mlflow_contracts')
    );

    expectedArguments(
        \app(),
        0,
        argumentsSet('mlflow_contracts')
    );
}

// Type hints for common patterns

namespace {

    if (false) {
        /**
         * @var \MLflow\MLflowClient              $mlflowClient
         * @var \MLflow\Api\ExperimentApi         $experimentApi
         * @var \MLflow\Api\RunApi                $runApi
         * @var \MLflow\Api\ModelRegistryApi      $modelRegistryApi
         * @var \MLflow\Builder\RunBuilder        $runBuilder
         * @var \MLflow\Builder\ExperimentBuilder $experimentBuilder
         * @var \MLflow\Builder\ModelBuilder      $modelBuilder
         * @var \MLflow\Testing\Fakes\MLflowFake  $mlflowFake
         */
        $_ide_helper_placeholder = null;
    }
}
