<?php

declare(strict_types=1);

namespace MLflow\Collection;

use MLflow\Model\Param;

/**
 * Type-safe collection for parameters
 *
 * @implements \IteratorAggregate<string, Param>
 * @implements \ArrayAccess<string, Param>
 */
class ParameterCollection implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    /** @var array<string, Param> */
    private array $params = [];

    /**
     * @param array<Param> $params
     */
    public function __construct(array $params = [])
    {
        foreach ($params as $param) {
            $this->add($param);
        }
    }

    /**
     * Create from associative array
     *
     * @param array<string, string> $data
     */
    public static function fromAssociativeArray(array $data): self
    {
        $collection = new self;
        foreach ($data as $key => $value) {
            $collection->add(new Param($key, $value));
        }

        return $collection;
    }

    /**
     * Create from array of param data
     *
     * @param array<array{key: string, value: string}> $data
     */
    public static function fromArrays(array $data): self
    {
        $collection = new self;
        foreach ($data as $paramData) {
            $collection->add(Param::fromArray($paramData));
        }

        return $collection;
    }

    public function add(Param $param): void
    {
        $this->params[$param->key] = $param;
    }

    public function get(string $key): ?Param
    {
        return $this->params[$key] ?? null;
    }

    public function getValue(string $key): ?string
    {
        $param = $this->get($key);

        return $param?->value;
    }

    public function has(string $key): bool
    {
        return isset($this->params[$key]);
    }

    public function remove(string $key): void
    {
        unset($this->params[$key]);
    }

    /**
     * @return array<string, Param>
     */
    public function all(): array
    {
        return $this->params;
    }

    /**
     * Get parameter values as associative array
     *
     * @return array<string, string>
     */
    public function toAssociativeArray(): array
    {
        $result = [];
        foreach ($this->params as $param) {
            $result[$param->key] = $param->value;
        }

        return $result;
    }

    /**
     * @return array<array{key: string, value: string}>
     */
    public function toArray(): array
    {
        return array_values(array_map(fn (Param $p) => $p->toArray(), $this->params));
    }

    /**
     * Filter parameters by predicate
     *
     * @param callable(Param): bool $predicate
     */
    public function filter(callable $predicate): self
    {
        $filtered = new self;
        foreach ($this->params as $param) {
            if ($predicate($param)) {
                $filtered->add($param);
            }
        }

        return $filtered;
    }

    /**
     * Filter parameters by key prefix
     */
    public function filterByKeyPrefix(string $prefix): self
    {
        return $this->filter(fn (Param $p) => str_starts_with($p->key, $prefix));
    }

    /**
     * Filter parameters by value pattern
     */
    public function filterByValuePattern(string $pattern): self
    {
        return $this->filter(fn (Param $p) => preg_match($pattern, $p->value) === 1);
    }

    /**
     * Merge with another collection
     */
    public function merge(self $other): self
    {
        $merged = new self($this->params);
        foreach ($other->params as $param) {
            $merged->add($param);
        }

        return $merged;
    }

    /**
     * Reduce collection to a single value
     *
     * @template TResult
     *
     * @param callable(TResult, Param): TResult $callback
     * @param TResult                           $initial
     *
     * @return TResult
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->params, $callback, $initial);
    }

    /**
     * @return array<string>
     */
    public function keys(): array
    {
        return array_keys($this->params);
    }

    /**
     * @return array<string>
     */
    public function values(): array
    {
        return array_map(fn (Param $p) => $p->value, $this->params);
    }

    public function count(): int
    {
        return count($this->params);
    }

    public function isEmpty(): bool
    {
        return empty($this->params);
    }

    /**
     * @return \ArrayIterator<string, Param>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->params);
    }

    /**
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

    public function offsetGet(mixed $offset): ?Param
    {
        return $this->get((string) $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($value instanceof Param) {
            if ($offset === null) {
                $this->add($value);
            } else {
                $this->params[(string) $offset] = $value;
            }
        } else {
            throw new \TypeError('Value must be an instance of Param');
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->remove((string) $offset);
    }

    public function equals(self $other): bool
    {
        if ($this->count() !== $other->count()) {
            return false;
        }

        foreach ($this->params as $key => $param) {
            $otherParam = $other->get($key);
            if ($otherParam === null || ! $param->equals($otherParam)) {
                return false;
            }
        }

        return true;
    }
}
