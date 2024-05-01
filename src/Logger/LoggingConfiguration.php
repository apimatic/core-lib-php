<?php

declare(strict_types=1);

namespace Core\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class LoggingConfiguration
{
    private const ALLOWED_LEVELS = [
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::INFO,
        LogLevel::DEBUG
    ];

    private $logger;
    private $level;
    private $maskSensitiveHeaders;
    private $requestConfig;
    private $responseConfig;

    public function __construct(
        ?AbstractLogger $logger,
        string $level,
        bool $maskSensitiveHeaders,
        LoggingRequestConfiguration $requestConfig,
        LoggingResponseConfiguration $responseConfig
    ) {
        $this->logger = $logger ?? new ConsoleLogger();
        $this->level = !in_array($level, self::ALLOWED_LEVELS) ? LogLevel::INFO : $level;
        $this->maskSensitiveHeaders = $maskSensitiveHeaders;
        $this->requestConfig = $requestConfig;
        $this->responseConfig = $responseConfig;
    }

    /**
     * Gets the logger instance used for logging.
     *
     * @return AbstractLogger The logger instance.
     */
    public function getLogger(): AbstractLogger
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
     * @return LoggingRequestConfiguration The request configuration.
     */
    public function getRequestConfig(): LoggingRequestConfiguration
    {
        return $this->requestConfig;
    }

    /**
     * Gets the response configuration for logging.
     *
     * @return LoggingResponseConfiguration The response configuration.
     */
    public function getResponseConfig(): LoggingResponseConfiguration
    {
        return $this->responseConfig;
    }
}
