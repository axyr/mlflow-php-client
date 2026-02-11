<?php

declare(strict_types=1);

namespace MLflow\Testing\Concerns;

use PHPUnit\Framework\Assert as PHPUnit;

trait AssertionsHelper
{
    /**
     * Assert that an experiment was created with the given name
     */
    public function assertExperimentCreated(string $name): void
    {
        $found = false;
        foreach ($this->recordedExperiments as $experiment) {
            if (($experiment['name'] ?? '') === $name) {
                $found = true;
                break;
            }
        }

        PHPUnit::assertTrue(
            $found,
            "Failed asserting that experiment [{$name}] was created."
        );
    }

    /**
     * Assert that a run was started for the given experiment
     */
    public function assertRunStarted(string $experimentId): void
    {
        $found = false;
        foreach ($this->recordedRuns as $run) {
            if (($run['experiment_id'] ?? '') === $experimentId) {
                $found = true;
                break;
            }
        }

        PHPUnit::assertTrue(
            $found,
            "Failed asserting that a run was started for experiment [{$experimentId}]."
        );
    }

    /**
     * Assert that a metric was logged for the given run
     */
    public function assertMetricLogged(string $runId, string $key, ?float $value = null): void
    {
        $found = false;
        foreach ($this->recordedMetrics as $metric) {
            if ($metric['run_id'] === $runId && $metric['key'] === $key) {
                /** @phpstan-ignore cast.double */
                $metricValue = (float) $metric['value'];
                if ($value === null || abs($metricValue - $value) < 0.0001) {
                    $found = true;
                    break;
                }
            }
        }

        $message = $value !== null
            ? "Failed asserting that metric [{$key}] with value [{$value}] was logged for run [{$runId}]."
            : "Failed asserting that metric [{$key}] was logged for run [{$runId}].";

        PHPUnit::assertTrue($found, $message);
    }

    /**
     * Assert that a parameter was logged for the given run
     */
    public function assertParamLogged(string $runId, string $key, ?string $value = null): void
    {
        $found = false;
        foreach ($this->recordedParams as $param) {
            if ($param['run_id'] === $runId && $param['key'] === $key) {
                if ($value === null || $param['value'] === $value) {
                    $found = true;
                    break;
                }
            }
        }

        $message = $value !== null
            ? "Failed asserting that param [{$key}] with value [{$value}] was logged for run [{$runId}]."
            : "Failed asserting that param [{$key}] was logged for run [{$runId}].";

        PHPUnit::assertTrue($found, $message);
    }

    /**
     * Assert that a tag was set for the given run
     */
    public function assertTagSet(string $runId, string $key, ?string $value = null): void
    {
        $found = false;
        foreach ($this->recordedTags as $tag) {
            if ($tag['run_id'] === $runId && $tag['key'] === $key) {
                if ($value === null || $tag['value'] === $value) {
                    $found = true;
                    break;
                }
            }
        }

        $message = $value !== null
            ? "Failed asserting that tag [{$key}] with value [{$value}] was set for run [{$runId}]."
            : "Failed asserting that tag [{$key}] was set for run [{$runId}].";

        PHPUnit::assertTrue($found, $message);
    }

    /**
     * Assert that a model was registered with the given name
     */
    public function assertModelRegistered(string $name): void
    {
        $found = false;
        foreach ($this->recordedModels as $model) {
            if (($model['name'] ?? '') === $name) {
                $found = true;
                break;
            }
        }

        PHPUnit::assertTrue(
            $found,
            "Failed asserting that model [{$name}] was registered."
        );
    }

    /**
     * Assert that nothing was logged (no experiments, runs, metrics, etc.)
     */
    public function assertNothingLogged(): void
    {
        PHPUnit::assertEmpty(
            $this->recordedExperiments,
            'Expected no experiments to be created, but some were.'
        );
        PHPUnit::assertEmpty(
            $this->recordedRuns,
            'Expected no runs to be started, but some were.'
        );
        PHPUnit::assertEmpty(
            $this->recordedMetrics,
            'Expected no metrics to be logged, but some were.'
        );
        PHPUnit::assertEmpty(
            $this->recordedParams,
            'Expected no params to be logged, but some were.'
        );
        PHPUnit::assertEmpty(
            $this->recordedModels,
            'Expected no models to be registered, but some were.'
        );
    }

    /**
     * Get the count of recorded experiments
     */
    public function getExperimentCount(): int
    {
        return count($this->recordedExperiments);
    }

    /**
     * Get the count of recorded runs
     */
    public function getRunCount(): int
    {
        return count($this->recordedRuns);
    }

    /**
     * Get the count of recorded metrics
     */
    public function getMetricCount(): int
    {
        return count($this->recordedMetrics);
    }

    /**
     * Get the count of recorded params
     */
    public function getParamCount(): int
    {
        return count($this->recordedParams);
    }

    /**
     * Get the count of recorded models
     */
    public function getModelCount(): int
    {
        return count($this->recordedModels);
    }
}
