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
    ) {}

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

    public function getSpanById(string $spanId): ?Span
    {
        foreach ($this->spans as $span) {
            if ($span->getSpanId() === $spanId) {
                return $span;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $spans = [];
        if (isset($data['spans']) && is_array($data['spans'])) {
            foreach ($data['spans'] as $spanData) {
                if (is_array($spanData)) {
                    /** @var array<string, mixed> $spanData */
                    $spans[] = Span::fromArray($spanData);
                }
            }
        }

        return new self($spans);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'spans' => array_map(fn (Span $span) => $span->toArray(), $this->spans),
        ];
    }
}
