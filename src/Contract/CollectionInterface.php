<?php

declare(strict_types=1);

namespace MLflow\Contract;

/**
 * Interface for all collection classes
 *
 * @template T
 * @extends \IteratorAggregate<array-key, T>
 */
interface CollectionInterface extends \Countable, \IteratorAggregate
{
    /**
     * Get all items in the collection
     *
     * @return array<T>
     */
    public function all(): array;

    /**
     * Check if collection is empty
     */
    public function isEmpty(): bool;

    /**
     * Get count of items
     */
    public function count(): int;

    /**
     * Get first item or null
     *
     * @return T|null
     */
    public function first(): mixed;

    /**
     * Get last item or null
     *
     * @return T|null
     */
    public function last(): mixed;

    /**
     * Filter collection by callback
     *
     * @param callable(T): bool $callback
     * @return static
     */
    public function filter(callable $callback): static;

    /**
     * Convert to array
     *
     * @return array<mixed>
     */
    public function toArray(): array;
}
