<?php

declare(strict_types=1);

namespace MLflow\Model;

use MLflow\Collection\MetricCollection;
use MLflow\Collection\ParameterCollection;
use MLflow\Collection\TagCollection;

/**
 * Represents data associated with an MLflow run (metrics, params, tags)
 */
readonly class RunData
{
    public MetricCollection $metrics;

    public ParameterCollection $params;

    /** @var TagCollection<RunTag> */
    public TagCollection $tags;

    /**
     * @param TagCollection<RunTag> $tags
     */
    public function __construct(
        ?MetricCollection $metrics = null,
        ?ParameterCollection $params = null,
        ?TagCollection $tags = null
    ) {
        $this->metrics = $metrics ?? new MetricCollection;
        $this->params = $params ?? new ParameterCollection;
        $this->tags = $tags ?? new TagCollection;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $metrics = new MetricCollection;
        if (isset($data['metrics']) && is_array($data['metrics'])) {
            foreach ($data['metrics'] as $metricData) {
                if (is_array($metricData)) {
                    /** @phpstan-ignore-next-line Array shape validated */
                    $metrics->add(Metric::fromArray($metricData));
                }
            }
        }

        $params = new ParameterCollection;
        if (isset($data['params']) && is_array($data['params'])) {
            foreach ($data['params'] as $paramData) {
                if (is_array($paramData)) {
                    /** @phpstan-ignore-next-line Array shape validated */
                    $params->add(Param::fromArray($paramData));
                }
            }
        }

        $tags = new TagCollection;
        if (isset($data['tags']) && is_array($data['tags'])) {
            foreach ($data['tags'] as $tagData) {
                if (is_array($tagData)) {
                    /** @phpstan-ignore-next-line Array shape validated */
                    $tags->add(RunTag::fromArray($tagData));
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
            'metrics' => $this->metrics->toArray(),
            'params' => $this->params->toArray(),
            'tags' => $this->tags->toArray(),
        ];
    }

    /**
     * Get metrics
     *
     * @return Metric[]
     *
     * @deprecated Access $metrics property directly
     */
    public function getMetrics(): array
    {
        return $this->metrics->all();
    }

    /**
     * Get params
     *
     * @return Param[]
     *
     * @deprecated Access $params property directly
     */
    public function getParams(): array
    {
        return array_values($this->params->all());
    }

    /**
     * Get tags
     *
     * @return RunTag[]
     *
     * @deprecated Access $tags property directly
     */
    public function getTags(): array
    {
        return array_values($this->tags->all());
    }

    public function getMetric(string $key): ?Metric
    {
        return $this->metrics->getByKey($key)->first();
    }

    public function getParam(string $key): ?Param
    {
        return $this->params->get($key);
    }

    public function getTag(string $key): ?RunTag
    {
        return $this->tags->get($key);
    }
}
