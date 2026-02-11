<?php

declare(strict_types=1);

namespace MLflow\Model;

/**
 * Represents data associated with an MLflow run (metrics, params, tags)
 */
readonly class RunData
{
    /** @var array<Metric> */
    public array $metrics;
    /** @var array<Param> */
    public array $params;
    /** @var array<RunTag> */
    public array $tags;

    /**
     * @param array<Metric> $metrics
     * @param array<Param> $params
     * @param array<RunTag> $tags
     */
    public function __construct(array $metrics = [], array $params = [], array $tags = [])
    {
        $this->metrics = $metrics;
        $this->params = $params;
        $this->tags = $tags;
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $metrics = [];
        if (isset($data['metrics']) && is_array($data['metrics'])) {
            foreach ($data['metrics'] as $metricData) {
                if (is_array($metricData)) {
                    /** @phpstan-ignore-next-line Array shape validated */
                    $metrics[] = Metric::fromArray($metricData);
                }
            }
        }

        $params = [];
        if (isset($data['params']) && is_array($data['params'])) {
            foreach ($data['params'] as $paramData) {
                if (is_array($paramData)) {
                    /** @phpstan-ignore-next-line Array shape validated */
                    $params[] = Param::fromArray($paramData);
                }
            }
        }

        $tags = [];
        if (isset($data['tags']) && is_array($data['tags'])) {
            foreach ($data['tags'] as $tagData) {
                if (is_array($tagData)) {
                    /** @phpstan-ignore-next-line Array shape validated */
                    $tags[] = RunTag::fromArray($tagData);
                }
            }
        }

        return new self($metrics, $params, $tags);
    }

    /**
     * @return array<string, mixed>
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
     * @deprecated Access $metrics property directly
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Get params
     * @return Param[]
     * @deprecated Access $params property directly
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Get tags
     * @return RunTag[]
     * @deprecated Access $tags property directly
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getMetric(string $key): ?Metric
    {
        foreach ($this->metrics as $metric) {
            if ($metric->key === $key) {
                return $metric;
            }
        }
        return null;
    }

    public function getParam(string $key): ?Param
    {
        foreach ($this->params as $param) {
            if ($param->key === $key) {
                return $param;
            }
        }
        return null;
    }

    public function getTag(string $key): ?RunTag
    {
        foreach ($this->tags as $tag) {
            if ($tag->key === $key) {
                return $tag;
            }
        }
        return null;
    }
}
