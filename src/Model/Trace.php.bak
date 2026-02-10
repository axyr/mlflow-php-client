<?php

declare(strict_types=1);

namespace MLflow\Model;

class Trace
{
    public function __construct(
        private TraceInfo $info,
        private TraceData $data
    ) {
    }

    public function getInfo(): TraceInfo
    {
        return $this->info;
    }

    public function getData(): TraceData
    {
        return $this->data;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            info: TraceInfo::fromArray($data['info'] ?? $data),
            data: TraceData::fromArray($data['data'] ?? $data)
        );
    }

    public function toArray(): array
    {
        return [
            'info' => $this->info->toArray(),
            'data' => $this->data->toArray(),
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}
