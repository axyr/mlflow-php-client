<?php

declare(strict_types=1);

namespace MLflow\Contract;

/**
 * Interface for models that support JSON serialization
 */
interface SerializableModelInterface extends ModelInterface, \JsonSerializable
{
    /**
     * Specify data which should be serialized to JSON
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array;
}
