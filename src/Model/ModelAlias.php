<?php

declare(strict_types=1);

namespace MLflow\Model;

/**
 * Represents an alias for a model version (immutable)
 */
readonly class ModelAlias implements \JsonSerializable, \Stringable
{
    public function __construct(
        public string $alias,
        public string $version,
    ) {
    }

    /**
     * Create ModelAlias from an array
     *
     * @param array{alias: string, version: string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            alias: $data['alias'],
            version: $data['version'],
        );
    }

    /**
     * Convert to array
     *
     * @return array{alias: string, version: string}
     */
    public function toArray(): array
    {
        return [
            'alias' => $this->alias,
            'version' => $this->version,
        ];
    }

    /**
     * JSON serialization
     *
     * @return array{alias: string, version: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return "{$this->alias}@v{$this->version}";
    }

    /**
     * Check equality with another ModelAlias
     */
    public function equals(self $other): bool
    {
        return $this->alias === $other->alias && $this->version === $other->version;
    }
}