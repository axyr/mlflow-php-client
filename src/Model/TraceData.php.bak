<?php

declare(strict_types=1);

namespace MLflow\Model;

class TraceData
{
    /**
     * @param Span[] $spans
     */
    public function __construct(
        private array $spans = []
    ) {
    }

    /**
     * @return Span[]
     */
    public function getSpans(): array
    {
        return $this->spans;
    }

    public function getRootSpan(): ?Span
    {
        foreach ($this->spans as $span) {
            if ($span->isRoot()) {
                return $span;
            }
        }
        return null;
    }

    /**
     * @param string $spanId
     */
    public function getSpanById(string $spanId): ?Span
    {
        foreach ($this->spans as $span) {
            if ($span->getSpanId() === $spanId) {
                return $span;
            }
        }
        return null;
    }

    public static function fromArray(array $data): self
    {
        $spans = [];
        if (isset($data['spans'])) {
            foreach ($data['spans'] as $spanData) {
                $spans[] = Span::fromArray($spanData);
            }
        }

        return new self($spans);
    }

    public function toArray(): array
    {
        return [
            'spans' => array_map(fn(Span $span) => $span->toArray(), $this->spans),
        ];
    }
}
