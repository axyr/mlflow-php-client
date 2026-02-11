<?php

declare(strict_types=1);

namespace MLflow\Config;

use MLflow\Exception\ConfigurationException;

/**
 * Type-safe configuration object for MLflow client
 */
readonly class MLflowConfig
{
    /**
     * @param float                 $timeout        Request timeout in seconds
     * @param int                   $connectTimeout Connection timeout in seconds
     * @param int                   $maxRetries     Maximum number of retry attempts
     * @param float                 $retryDelay     Delay between retries in seconds
     * @param array<string, string> $headers        Additional HTTP headers
     * @param bool                  $verify         Verify SSL certificates
     * @param string|null           $proxy          Proxy server URL
     * @param string|null           $cert           Path to SSL certificate
     * @param string|null           $sslKey         Path to SSL key
     * @param bool                  $debug          Enable debug mode
     *
     * @throws ConfigurationException If configuration is invalid
     */
    public function __construct(
        public float $timeout = 30.0,
        public int $connectTimeout = 10,
        public int $maxRetries = 3,
        public float $retryDelay = 1.0,
        public array $headers = [],
        public bool $verify = true,
        public ?string $proxy = null,
        public ?string $cert = null,
        public ?string $sslKey = null,
        public bool $debug = false,
    ) {
        if ($this->timeout <= 0) {
            throw new ConfigurationException('Timeout must be positive');
        }

        if ($this->connectTimeout <= 0) {
            throw new ConfigurationException('Connect timeout must be positive');
        }

        if ($this->maxRetries < 0) {
            throw new ConfigurationException('Max retries cannot be negative');
        }

        if ($this->retryDelay < 0) {
            throw new ConfigurationException('Retry delay cannot be negative');
        }
    }

    /**
     * Create config from array (backward compatibility)
     *
     * @param array<string, mixed> $config Configuration array
     */
    public static function fromArray(array $config): self
    {
        $headers = $config['headers'] ?? null;
        if (is_array($headers)) {
            /** @var array<string, string> $headers */
        } else {
            $headers = [];
        }

        return new self(
            timeout: is_numeric($config['timeout'] ?? null) ? (float) $config['timeout'] : 30.0,
            connectTimeout: is_int($config['connect_timeout'] ?? null) ? $config['connect_timeout'] : 10,
            maxRetries: is_int($config['retries'] ?? null) ? $config['retries'] : 3,
            retryDelay: is_numeric($config['retry_delay'] ?? null) ? (float) $config['retry_delay'] : 1.0,
            headers: $headers,
            verify: is_bool($config['verify'] ?? null) ? $config['verify'] : true,
            proxy: is_string($config['proxy'] ?? null) ? $config['proxy'] : null,
            cert: is_string($config['cert'] ?? null) ? $config['cert'] : null,
            sslKey: is_string($config['ssl_key'] ?? null) ? $config['ssl_key'] : null,
            debug: is_bool($config['debug'] ?? null) ? $config['debug'] : false,
        );
    }

    /**
     * Create default configuration
     */
    public static function default(): self
    {
        return new self;
    }

    /**
     * Convert to Guzzle-compatible array
     *
     * @return array<string, mixed>
     */
    public function toGuzzleArray(): array
    {
        $config = [
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
            'headers' => $this->headers,
            'verify' => $this->verify,
        ];

        if ($this->proxy !== null) {
            $config['proxy'] = $this->proxy;
        }

        if ($this->cert !== null) {
            $config['cert'] = $this->cert;
        }

        if ($this->sslKey !== null) {
            $config['ssl_key'] = $this->sslKey;
        }

        if ($this->debug) {
            $config['debug'] = true;
        }

        return $config;
    }

    /**
     * Create a new instance with modified timeout
     */
    public function withTimeout(float $timeout): self
    {
        return new self(
            timeout: $timeout,
            connectTimeout: $this->connectTimeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            headers: $this->headers,
            verify: $this->verify,
            proxy: $this->proxy,
            cert: $this->cert,
            sslKey: $this->sslKey,
            debug: $this->debug,
        );
    }

    /**
     * Create a new instance with additional headers
     *
     * @param array<string, string> $headers
     */
    public function withHeaders(array $headers): self
    {
        return new self(
            timeout: $this->timeout,
            connectTimeout: $this->connectTimeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            headers: array_merge($this->headers, $headers),
            verify: $this->verify,
            proxy: $this->proxy,
            cert: $this->cert,
            sslKey: $this->sslKey,
            debug: $this->debug,
        );
    }

    /**
     * Create a new instance with modified max retries
     */
    public function withMaxRetries(int $maxRetries): self
    {
        return new self(
            timeout: $this->timeout,
            connectTimeout: $this->connectTimeout,
            maxRetries: $maxRetries,
            retryDelay: $this->retryDelay,
            headers: $this->headers,
            verify: $this->verify,
            proxy: $this->proxy,
            cert: $this->cert,
            sslKey: $this->sslKey,
            debug: $this->debug,
        );
    }

    /**
     * Create a new instance with debug mode enabled
     */
    public function withDebug(bool $debug = true): self
    {
        return new self(
            timeout: $this->timeout,
            connectTimeout: $this->connectTimeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            headers: $this->headers,
            verify: $this->verify,
            proxy: $this->proxy,
            cert: $this->cert,
            sslKey: $this->sslKey,
            debug: $debug,
        );
    }

    /**
     * Create a new instance with SSL verification disabled (not recommended for production)
     */
    public function withoutSslVerification(): self
    {
        return new self(
            timeout: $this->timeout,
            connectTimeout: $this->connectTimeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            headers: $this->headers,
            verify: false,
            proxy: $this->proxy,
            cert: $this->cert,
            sslKey: $this->sslKey,
            debug: $this->debug,
        );
    }
}
