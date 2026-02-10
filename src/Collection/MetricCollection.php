<?php

declare(strict_types=1);

namespace MLflow\Collection;

use MLflow\Model\Metric;

/**
 * Type-safe collection for metrics
 *
 * @implements \IteratorAggregate<int, Metric>
 */
class MetricCollection implements \Countable, \IteratorAggregate, \JsonSerializable
{
    /** @var array<Metric> */
    private array $metrics = [];

    /**
     * @param array<Metric> $metrics
     */
    public function __construct(array $metrics = [])
    {
        foreach ($metrics as $metric) {
            $this->add($metric);
        }
    }

    /**
     * Create from array of metric data
     *
     * @param array<array{key: string, value: float|int|string, timestamp: int|string, step?: int|string}> $data
     */
    public static function fromArrays(array $data): self
    {
        $collection = new self();
        foreach ($data as $metricData) {
            $collection->add(Metric::fromArray($metricData));
        }

        return $collection;
    }

    /**
     * Add a metric to the collection
     */
    public function add(Metric $metric): void
    {
        $this->metrics[] = $metric;
    }

    /**
     * Get all metrics
     *
     * @return array<Metric>
     */
    public function all(): array
    {
        return $this->metrics;
    }

    /**
     * Get metrics for a specific key
     *
     * @return self
     */
    public function getByKey(string $key): self
    {
        return $this->filter(fn(Metric $m) => $m->key === $key);
    }

    /**
     * Get metrics for a specific step
     *
     * @return self
     */
    public function getByStep(int $step): self
    {
        return $this->filter(fn(Metric $m) => $m->step === $step);
    }

    /**
     * Get the latest metric for each key
     *
     * @return array<string, Metric>
     */
    public function getLatestByKey(): array
    {
        $latest = [];
        foreach ($this->metrics as $metric) {
            if (!isset($latest[$metric->key]) || $metric->timestamp > $latest[$metric->key]->timestamp) {
                $latest[$metric->key] = $metric;
            }
        }

        return $latest;
    }

    /**
     * Filter metrics by predicate
     *
     * @param callable(Metric): bool $predicate
     * @return self
     */
    public function filter(callable $predicate): self
    {
        return new self(array_filter($this->metrics, $predicate));
    }

    /**
     * Sort metrics
     *
     * @param callable(Metric, Metric): int $comparator
     * @return self
     */
    public function sort(callable $comparator): self
    {
        $sorted = $this->metrics;
        usort($sorted, $comparator);

        return new self($sorted);
    }

    /**
     * Sort by timestamp ascending
     *
     * @return self
     */
    public function sortByTimestamp(): self
    {
        return $this->sort(fn(Metric $a, Metric $b) => $a->timestamp <=> $b->timestamp);
    }

    /**
     * Sort by step ascending
     *
     * @return self
     */
    public function sortByStep(): self
    {
        return $this->sort(fn(Metric $a, Metric $b) => $a->step <=> $b->step);
    }

    /**
     * Group metrics by key
     *
     * @return array<string, self>
     */
    public function groupByKey(): array
    {
        $grouped = [];
        foreach ($this->metrics as $metric) {
            if (!isset($grouped[$metric->key])) {
                $grouped[$metric->key] = new self();
            }
            $grouped[$metric->key]->add($metric);
        }

        return $grouped;
    }

    /**
     * Group metrics by step
     *
     * @return array<int, self>
     */
    public function groupByStep(): array
    {
        $grouped = [];
        foreach ($this->metrics as $metric) {
            if (!isset($grouped[$metric->step])) {
                $grouped[$metric->step] = new self();
            }
            $grouped[$metric->step]->add($metric);
        }

        return $grouped;
    }

    /**
     * Get min/max values for a specific key
     *
     * @return array{min: float, max: float}|null
     */
    public function getMinMax(string $key): ?array
    {
        $metrics = $this->getByKey($key)->all();
        if (empty($metrics)) {
            return null;
        }

        $values = array_map(fn(Metric $m) => $m->value, $metrics);

        return [
            'min' => min($values),
            'max' => max($values),
        ];
    }

    /**
     * Get average value for a specific key
     */
    public function getAverage(string $key): ?float
    {
        $metrics = $this->getByKey($key)->all();
        if (empty($metrics)) {
            return null;
        }

        $sum = array_sum(array_map(fn(Metric $m) => $m->value, $metrics));

        return $sum / count($metrics);
    }

    /**
     * Convert to array
     *
     * @return array<array{key: string, value: float, timestamp: int, step: int}>
     */
    public function toArray(): array
    {
        return array_map(fn(Metric $m) => $m->toArray(), $this->metrics);
    }

    /**
     * Count metrics
     */
    public function count(): int
    {
        return count($this->metrics);
    }

    /**
     * Check if collection is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->metrics);
    }

    /**
     * Get first metric
     */
    public function first(): ?Metric
    {
        return $this->metrics[0] ?? null;
    }

    /**
     * Get last metric
     */
    public function last(): ?Metric
    {
        return empty($this->metrics) ? null : $this->metrics[array_key_last($this->metrics)];
    }

    /**
     * Get iterator
     *
     * @return \ArrayIterator<int, Metric>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->metrics);
    }

    /**
     * JSON serialization
     *
     * @return array<array{key: string, value: float, timestamp: int, step: int}>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}