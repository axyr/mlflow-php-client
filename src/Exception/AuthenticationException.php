<?php

declare(strict_types=1);

namespace MLflow\Exception;

/**
 * Exception thrown for authentication/authorization errors (HTTP 401, 403)
 */
class AuthenticationException extends ApiException {}
