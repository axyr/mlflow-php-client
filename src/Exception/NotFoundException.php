<?php

declare(strict_types=1);

namespace MLflow\Exception;

/**
 * Exception thrown when a requested resource is not found (HTTP 404)
 */
class NotFoundException extends ApiException {}
