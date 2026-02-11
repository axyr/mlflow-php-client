<?php

declare(strict_types=1);

namespace MLflow\Model;

abstract class TraceLocation
{
    /**
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

    /**
     * @param array<string, mixed> $data
     */
    abstract public static function fromArray(array $data): self;
}
