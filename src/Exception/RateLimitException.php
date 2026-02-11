<?php

declare(strict_types=1);

namespace MLflow\Exception;

/**
 * Exception thrown when rate limit is exceeded (HTTP 429)
 */
class RateLimitException extends ApiException
{
}
