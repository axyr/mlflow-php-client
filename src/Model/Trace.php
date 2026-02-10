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

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $info = $data['info'] ?? $data;
        $dataArray = $data['data'] ?? $data;

        return new self(
            info: TraceInfo::fromArray(is_array($info) ? $info : []),
            data: TraceData::fromArray(is_array($dataArray) ? $dataArray : [])
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'info' => $this->info->toArray(),
            'data' => $this->data->toArray(),
        ];
    }

    public function toJson(): string
    {
        $json = json_encode($this->toArray(), JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode trace to JSON: ' . json_last_error_msg());
        }
        return $json;
    }
}
