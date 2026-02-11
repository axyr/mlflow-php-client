<?php

declare(strict_types=1);

namespace MLflow\Constants;

/**
 * HTTP status code constants
 */
final class HttpStatus
{
    // 2xx Success
    public const OK = 200;
    public const CREATED = 201;
    public const ACCEPTED = 202;
    public const NO_CONTENT = 204;

    // 4xx Client Errors
    public const BAD_REQUEST = 400;
    public const UNAUTHORIZED = 401;
    public const FORBIDDEN = 403;
    public const NOT_FOUND = 404;
    public const METHOD_NOT_ALLOWED = 405;
    public const REQUEST_TIMEOUT = 408;
    public const CONFLICT = 409;
    public const UNPROCESSABLE_ENTITY = 422;
    public const TOO_MANY_REQUESTS = 429;

    // 5xx Server Errors
    public const INTERNAL_SERVER_ERROR = 500;
    public const BAD_GATEWAY = 502;
    public const SERVICE_UNAVAILABLE = 503;
    public const GATEWAY_TIMEOUT = 504;

    private function __construct()
    {
        // Prevent instantiation
    }
}
