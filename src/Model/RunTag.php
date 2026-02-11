<?php

declare(strict_types=1);

namespace MLflow\Model;

use MLflow\Contract\SerializableModelInterface;

/**
 * Represents an MLflow run tag (immutable key-value pair)
 */
readonly class RunTag implements SerializableModelInterface, \Stringable
{
    public function __construct(
        public string $key,
        public string $value,
    ) {
    }

    /**
     * @param array{key: string, value: string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: $data['key'],
            value: $data['value'],
        );
    }

    /**
     * @return array{key: string, value: string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
        ];
    }

    /**
     * @return array{key: string, value: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return "{$this->key}:{$this->value}";
    }

    public function equals(self $other): bool
    {
        return $this->key === $other->key && $this->value === $other->value;
    }

    public function isSystemTag(): bool
    {
        return str_starts_with($this->key, 'mlflow.');
    }
}