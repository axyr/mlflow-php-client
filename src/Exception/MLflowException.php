<?php

declare(strict_types=1);

namespace MLflow\Exception;

use Exception;

/**
 * Base exception class for MLflow client
 */
class MLflowException extends Exception
{
    protected ?array $context = null;

    /**
     * Create a new MLflow exception with optional context
     *
     * @param string $message The exception message
     * @param int $code The exception code
     * @param array|null $context Additional context information
     * @param Exception|null $previous The previous exception
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?array $context = null,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get the exception context
     */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /**
     * Create an exception from an HTTP error
     */
    public static function fromHttpError(int $statusCode, string $message, ?array $body = null): self
    {
        $errorMessage = sprintf('HTTP %d: %s', $statusCode, $message);

        if ($body && isset($body['message'])) {
            $errorMessage = sprintf('HTTP %d: %s', $statusCode, $body['message']);
        }

        return new self($errorMessage, $statusCode, $body);
    }
}