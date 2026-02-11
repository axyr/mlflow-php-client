<?php

declare(strict_types=1);

namespace MLflow\Contract;

/**
 * Interface for all MLflow model classes
 */
interface ModelInterface
{
    /**
     * Create model instance from array data
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self;

    /**
     * Convert model to array representation
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
