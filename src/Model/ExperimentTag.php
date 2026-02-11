<?php

declare(strict_types=1);

namespace MLflow\Model;

use MLflow\Contract\SerializableModelInterface;

/**
 * Represents a tag on an experiment (immutable key-value pair)
 */
readonly class ExperimentTag implements \Stringable, SerializableModelInterface
{
    public function __construct(
        public string $key,
        public string $value,
    ) {}

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
     * Create a tag with any value type (automatically converted to string)
     *
     * @param string $key   Tag key
     * @param mixed  $value Tag value (will be converted to string)
     *
     * @example
     * ```php
     * $tag = ExperimentTag::create('team', 'ml-team');
     * $tag = ExperimentTag::create('version', 2);
     * ```
     */
    public static function create(string $key, mixed $value): self
    {
        return new self(
            key: $key,
            value: is_string($value) ? $value : (string) json_encode($value),
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
}
