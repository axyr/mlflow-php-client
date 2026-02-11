<?php

declare(strict_types=1);

namespace MLflow\Model;

use MLflow\Contract\SerializableModelInterface;
use MLflow\Util\ValidationHelper;

/**
 * Represents an MLflow parameter (immutable key-value pair)
 */
readonly class Param implements SerializableModelInterface, \Stringable
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
            key: ValidationHelper::requireString($data, 'key'),
            value: ValidationHelper::requireString($data, 'value'),
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
        return "{$this->key}={$this->value}";
    }

    public function equals(self $other): bool
    {
        return $this->key === $other->key && $this->value === $other->value;
    }
}