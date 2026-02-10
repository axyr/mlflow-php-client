<?php

declare(strict_types=1);

namespace MLflow\Model;

/**
 * Represents the history of a metric
 */
class MetricHistory
{
    private string $key;
    /** @var Metric[] */
    private array $history;

    /**
     * @param string $key
     * @param Metric[] $history
     */
    public function __construct(string $key, array $history)
    {
        $this->key = $key;
        $this->history = $history;
    }

    /**
     * Create MetricHistory from an array
     */
    public static function fromArray(array $data): self
    {
        $history = [];
        foreach ($data['metrics'] ?? [] as $metricData) {
            $history[] = Metric::fromArray($metricData);
        }

        return new self(
            $data['key'],
            $history
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'metrics' => array_map(fn($m) => $m->toArray(), $this->history),
        ];
    }

    // Getters
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return Metric[]
     */
    public function getHistory(): array
    {
        return $this->history;
    }

    /**
     * Get the latest metric value
     */
    public function getLatest(): ?Metric
    {
        if (empty($this->history)) {
            return null;
        }

        // Sort by timestamp descending
        $sorted = $this->history;
        usort($sorted, fn($a, $b) => $b->getTimestamp() <=> $a->getTimestamp());

        return $sorted[0];
    }

    /**
     * Get metric at specific step
     */
    public function getAtStep(int $step): ?Metric
    {
        foreach ($this->history as $metric) {
            if ($metric->getStep() === $step) {
                return $metric;
            }
        }
        return null;
    }
}