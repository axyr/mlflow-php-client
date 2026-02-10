<?php

declare(strict_types=1);

namespace MLflow\Model;

use MLflow\Enum\TraceState;

class TraceInfo
{
    /**
     * @param string $traceId 32-character hex string
     * @param TraceLocation $traceLocation Experiment or inference table
     * @param int $requestTime Milliseconds since epoch
     * @param TraceState $state OK, ERROR, IN_PROGRESS
     * @param string|null $requestPreview Preview of request
     * @param string|null $responsePreview Preview of response
     * @param string|null $clientRequestId Client-provided request ID
     * @param int|null $executionDuration Duration in milliseconds
     * @param array<string, string> $traceMetadata Metadata key-value pairs
     * @param array<string, string> $tags Tags key-value pairs
     */
    public function __construct(
        private string $traceId,
        private TraceLocation $traceLocation,
        private int $requestTime,
        private TraceState $state,
        private ?string $requestPreview = null,
        private ?string $responsePreview = null,
        private ?string $clientRequestId = null,
        private ?int $executionDuration = null,
        private array $traceMetadata = [],
        private array $tags = []
    ) {
    }

    public function getTraceId(): string
    {
        return $this->traceId;
    }

    public function getTraceLocation(): TraceLocation
    {
        return $this->traceLocation;
    }

    public function getRequestTime(): int
    {
        return $this->requestTime;
    }

    public function getState(): TraceState
    {
        return $this->state;
    }

    public function getRequestPreview(): ?string
    {
        return $this->requestPreview;
    }

    public function getResponsePreview(): ?string
    {
        return $this->responsePreview;
    }

    public function getClientRequestId(): ?string
    {
        return $this->clientRequestId;
    }

    public function getExecutionDuration(): ?int
    {
        return $this->executionDuration;
    }

    /**
     * @return array<string, string>
     */
    public function getTraceMetadata(): array
    {
        return $this->traceMetadata;
    }

    /**
     * @return array<string, string>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @deprecated Use getTraceId()
     */
    public function getRequestId(): string
    {
        return $this->traceId;
    }

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        // Parse trace location
        $locationData = $data['trace_location'] ?? $data['location'] ?? [];
        if (!is_array($locationData)) {
            $locationData = [];
        }
        $locationType = is_string($locationData['type'] ?? null) ? $locationData['type'] : 'MLFLOW_EXPERIMENT';

        if ($locationType === 'MLFLOW_EXPERIMENT') {
            $location = MlflowExperimentLocation::fromArray($locationData);
        } else {
            // Default to experiment location if type unknown
            $experimentId = $locationData['experiment_id'] ?? '0';
            $location = new MlflowExperimentLocation(is_string($experimentId) ? $experimentId : '0');
        }

        $traceId = $data['trace_id'] ?? $data['request_id'] ?? ''; // Support deprecated request_id
        $requestTime = $data['request_time'] ?? 0;
        $stateValue = $data['state'] ?? $data['status'] ?? 'OK'; // Support deprecated status
        $requestPreview = $data['request_preview'] ?? null;
        $responsePreview = $data['response_preview'] ?? null;
        $clientRequestId = $data['client_request_id'] ?? null;
        $executionDuration = $data['execution_duration'] ?? null;
        $traceMetadata = $data['trace_metadata'] ?? [];
        $tags = $data['tags'] ?? [];

        return new self(
            traceId: is_string($traceId) ? $traceId : '',
            traceLocation: $location,
            requestTime: is_int($requestTime) ? $requestTime : (is_numeric($requestTime) ? (int) $requestTime : 0),
            state: (is_string($stateValue) || is_int($stateValue)) ? TraceState::from($stateValue) : TraceState::OK,
            requestPreview: is_string($requestPreview) ? $requestPreview : null,
            responsePreview: is_string($responsePreview) ? $responsePreview : null,
            clientRequestId: is_string($clientRequestId) ? $clientRequestId : null,
            executionDuration: is_int($executionDuration) ? $executionDuration : (is_numeric($executionDuration) ? (int) $executionDuration : null),
            traceMetadata: is_array($traceMetadata) ? $traceMetadata : [],
            tags: is_array($tags) ? $tags : []
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'trace_id' => $this->traceId,
            'trace_location' => $this->traceLocation->toArray(),
            'request_time' => $this->requestTime,
            'state' => $this->state->value,
            'trace_metadata' => $this->traceMetadata,
            'tags' => $this->tags,
        ];

        if ($this->requestPreview !== null) {
            $data['request_preview'] = $this->requestPreview;
        }

        if ($this->responsePreview !== null) {
            $data['response_preview'] = $this->responsePreview;
        }

        if ($this->clientRequestId !== null) {
            $data['client_request_id'] = $this->clientRequestId;
        }

        if ($this->executionDuration !== null) {
            $data['execution_duration'] = $this->executionDuration;
        }

        return $data;
    }
}
