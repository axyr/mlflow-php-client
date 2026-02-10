<?php

declare(strict_types=1);

namespace MLflow\Collection;

use MLflow\Model\ExperimentTag;
use MLflow\Model\ModelTag;
use MLflow\Model\RunTag;

/**
 * Type-safe collection for tags (key-value pairs)
 *
 * @template T of ExperimentTag|RunTag|ModelTag
 * @implements \IteratorAggregate<string, T>
 * @implements \ArrayAccess<string, T>
 */
class TagCollection implements \Countable, \IteratorAggregate, \JsonSerializable, \ArrayAccess
{
    /** @var array<string, T> */
    private array $tags = [];

    /**
     * @param array<T> $tags
     */
    public function __construct(array $tags = [])
    {
        foreach ($tags as $tag) {
            $this->add($tag);
        }
    }

    /**
     * Create from associative array
     *
     * @template TagType of ExperimentTag|RunTag|ModelTag
     * @param array<string, string> $data
     * @param class-string<TagType> $tagClass
     * @return self<TagType>
     */
    public static function fromAssociativeArray(array $data, string $tagClass): self
    {
        $collection = new self();
        foreach ($data as $key => $value) {
            $collection->add($tagClass::fromArray(['key' => $key, 'value' => $value]));
        }

        return $collection;
    }

    /**
     * Create from array of tag arrays
     *
     * @template TagType of ExperimentTag|RunTag|ModelTag
     * @param array<array{key: string, value: string}> $data
     * @param class-string<TagType> $tagClass
     * @return self<TagType>
     */
    public static function fromArrays(array $data, string $tagClass): self
    {
        $collection = new self();
        foreach ($data as $tagData) {
            $collection->add($tagClass::fromArray($tagData));
        }

        return $collection;
    }

    /**
     * Add a tag to the collection
     *
     * @param T $tag
     */
    public function add(ExperimentTag|RunTag|ModelTag $tag): void
    {
        $this->tags[$tag->key] = $tag;
    }

    /**
     * Get a tag by key
     *
     * @return T|null
     */
    public function get(string $key): ExperimentTag|RunTag|ModelTag|null
    {
        return $this->tags[$key] ?? null;
    }

    /**
     * Check if a tag exists
     */
    public function has(string $key): bool
    {
        return isset($this->tags[$key]);
    }

    /**
     * Remove a tag
     */
    public function remove(string $key): void
    {
        unset($this->tags[$key]);
    }

    /**
     * Get all tags
     *
     * @return array<string, T>
     */
    public function all(): array
    {
        return $this->tags;
    }

    /**
     * Get tag values as associative array
     *
     * @return array<string, string>
     */
    public function toAssociativeArray(): array
    {
        $result = [];
        foreach ($this->tags as $tag) {
            $result[$tag->key] = $tag->value;
        }

        return $result;
    }

    /**
     * Convert to array of tag arrays
     *
     * @return array<array{key: string, value: string}>
     */
    public function toArray(): array
    {
        return array_values(array_map(fn($tag) => $tag->toArray(), $this->tags));
    }

    /**
     * Filter tags by predicate
     *
     * @param callable(T): bool $predicate
     * @return self<T>
     */
    public function filter(callable $predicate): self
    {
        $filtered = new self();
        foreach ($this->tags as $tag) {
            if ($predicate($tag)) {
                $filtered->add($tag);
            }
        }

        return $filtered;
    }

    /**
     * Filter system tags (starting with mlflow.)
     *
     * @return self<T>
     */
    public function filterSystemTags(): self
    {
        return $this->filter(fn($tag) => str_starts_with($tag->key, 'mlflow.'));
    }

    /**
     * Filter user tags (not starting with mlflow.)
     *
     * @return self<T>
     */
    public function filterUserTags(): self
    {
        return $this->filter(fn($tag) => !str_starts_with($tag->key, 'mlflow.'));
    }

    /**
     * Merge with another collection
     *
     * @param self<T> $other
     * @return self<T>
     */
    public function merge(self $other): self
    {
        $merged = new self($this->tags);
        foreach ($other->tags as $tag) {
            $merged->add($tag);
        }

        return $merged;
    }

    /**
     * Count tags
     */
    public function count(): int
    {
        return count($this->tags);
    }

    /**
     * Check if collection is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->tags);
    }

    /**
     * Get iterator
     *
     * @return \ArrayIterator<string, T>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->tags);
    }

    /**
     * JSON serialization
     *
     * @return array<array{key: string, value: string}>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    // ArrayAccess implementation

    public function offsetExists(mixed $offset): bool
    {
        return $this->has((string) $offset);
    }

    public function offsetGet(mixed $offset): ExperimentTag|RunTag|ModelTag|null
    {
        return $this->get((string) $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->add($value);
        } else {
            $this->tags[(string) $offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->remove((string) $offset);
    }
}