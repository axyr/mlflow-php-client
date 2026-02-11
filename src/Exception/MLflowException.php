<?php

declare(strict_types=1);

namespace MLflow\Exception;

use Exception;

/**
 * Base exception class for MLflow client
 */
class MLflowException extends Exception
{
    /** @var array<string, mixed>|null */
    protected ?array $context = null;

    /**
     * Create a new MLflow exception with optional context
     *
     * @param string $message The exception message
     * @param int $code The exception code
     * @param array<string, mixed>|null $context Additional context information
     * @param \Throwable|null $previous The previous exception
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?array $context = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get the exception context
     *
     * @return array<string, mixed>|null
     */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /**
     * Create an exception from an HTTP error
     *
     * Maps HTTP status codes to specific exception types for better error handling
     *
     * @param int $statusCode HTTP status code
     * @param string $message Error message
     * @param array<string, mixed>|null $body Response body
     * @return self
     */
    public static function fromHttpError(int $statusCode, string $message, ?array $body = null): self
    {
        $errorMessage = sprintf('HTTP %d: %s', $statusCode, $message);

        if ($body && isset($body['message']) && is_string($body['message'])) {
            $errorMessage = sprintf('HTTP %d: %s', $statusCode, $body['message']);
        }

        return match ($statusCode) {
            404 => new NotFoundException($errorMessage, $statusCode, $body),
            401, 403 => new AuthenticationException($errorMessage, $statusCode, $body),
            429 => new RateLimitException($errorMessage, $statusCode, $body),
            408, 504 => new TimeoutException($errorMessage, $statusCode, $body),
            409 => new ConflictException($errorMessage, $statusCode, $body),
            400, 422 => new ValidationException($errorMessage, $statusCode, $body),
            default => new ApiException($errorMessage, $statusCode, $body),
        };
    }
}
