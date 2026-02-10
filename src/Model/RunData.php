<?php

declare(strict_types=1);

namespace MLflow\Model;

/**
 * Represents data associated with an MLflow run (metrics, params, tags)
 */
class RunData
{
    private array $metrics;
    private array $params;
    private array $tags;

    public function __construct(array $metrics = [], array $params = [], array $tags = [])
    {
        $this->metrics = $metrics;
        $this->params = $params;
        $this->tags = $tags;
    }

    /**
     * Create RunData from an array
     */
    public static function fromArray(array $data): self
    {
        $metrics = [];
        if (isset($data['metrics'])) {
            foreach ($data['metrics'] as $metricData) {
                $metrics[] = Metric::fromArray($metricData);
            }
        }

        $params = [];
        if (isset($data['params'])) {
            foreach ($data['params'] as $paramData) {
                $params[] = Param::fromArray($paramData);
            }
        }

        $tags = [];
        if (isset($data['tags'])) {
            foreach ($data['tags'] as $tagData) {
                $tags[] = RunTag::fromArray($tagData);
            }
        }

        return new self($metrics, $params, $tags);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'metrics' => array_map(fn($m) => $m->toArray(), $this->metrics),
            'params' => array_map(fn($p) => $p->toArray(), $this->params),
            'tags' => array_map(fn($t) => $t->toArray(), $this->tags),
        ];
    }

    /**
     * Get metrics
     * @return Metric[]
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Get params
     * @return Param[]
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Get tags
     * @return RunTag[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * Get metric by key
     */
    public function getMetric(string $key): ?Metric
    {
        foreach ($this->metrics as $metric) {
            if ($metric->getKey() === $key) {
                return $metric;
            }
        }
        return null;
    }

    /**
     * Get param by key
     */
    public function getParam(string $key): ?Param
    {
        foreach ($this->params as $param) {
            if ($param->getKey() === $key) {
                return $param;
            }
        }
        return null;
    }

    /**
     * Get tag by key
     */
    public function getTag(string $key): ?RunTag
    {
        foreach ($this->tags as $tag) {
            if ($tag->getKey() === $key) {
                return $tag;
            }
        }
        return null;
    }
}