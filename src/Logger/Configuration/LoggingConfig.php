<?php

declare(strict_types=1);

namespace Core\Logger\Configuration;

use Core\Logger\ConsoleLogger;
use Psr\Log\LoggerInterface;

class LoggingConfig
{
    private $logger;
    private $level;
    private $maskSensitiveHeaders;
    private $requestConfig;
    private $responseConfig;

    public function __construct(
        ?LoggerInterface $logger,
        string $level,
        bool $maskSensitiveHeaders,
        RequestConfig $requestConfig,
        ResponseConfig $responseConfig
    ) {
        $this->logger = $logger ?? new ConsoleLogger();
        $this->level = $level;
        $this->maskSensitiveHeaders = $maskSensitiveHeaders;
        $this->requestConfig = $requestConfig;
        $this->responseConfig = $responseConfig;
    }

    /**
     * Gets the logger instance used for logging.
     *
     * @return LoggerInterface The logger instance.
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Gets the level of logging.
     *
     * Returns a string representing the level of logging. See Psr\Log\LogLevel.php
     * for possible values of log levels.
     *
     * @return string The level of logging.
     */
    public function getLevel(): string
    {
        return $this->level;
    }

    /**
     * Indicates whether sensitive headers should be masked in logs.
     *
     * @return bool True if sensitive headers should be masked, false otherwise.
     */
    public function shouldMaskSensitiveHeaders(): bool
    {
        return $this->maskSensitiveHeaders;
    }

    /**
     * Gets the request configuration for logging.
     *
     * @return RequestConfig The request configuration.
     */
    public function getRequestConfig(): RequestConfig
    {
        return $this->requestConfig;
    }

    /**
     * Gets the response configuration for logging.
     *
     * @return ResponseConfig The response configuration.
     */
    public function getResponseConfig(): ResponseConfig
    {
        return $this->responseConfig;
    }
}
