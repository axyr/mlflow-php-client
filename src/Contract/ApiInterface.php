<?php

declare(strict_types=1);

namespace MLflow\Contract;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Interface for all API classes
 */
interface ApiInterface
{
    /**
     * Construct API with HTTP client and logger
     */
    public function __construct(ClientInterface $client, ?LoggerInterface $logger = null);
}
