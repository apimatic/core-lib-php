<?php

declare(strict_types=1);

namespace Core\Logger;

use CoreInterfaces\Core\Logger\LoggingConfigInterface;
use CoreInterfaces\Core\Logger\LoggingRequestConfigInterface;
use CoreInterfaces\Core\Logger\LoggingResponseConfigInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LoggingConfiguration implements LoggingConfigInterface
{
    private $logger;
    private $level;
    private $maskSensitiveHeaders;
    private $requestConfig;
    private $responseConfig;

    public function __construct(
        ?AbstractLogger $logger,
        string $level,
        bool $maskSensitiveHeaders,
        LoggingRequestConfigInterface $requestConfig,
        LoggingResponseConfigInterface $responseConfig
    ) {
        $this->logger = $logger ?? new ConsoleLogger();
        $this->level = !in_array($level, [
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG
        ]) ? LogLevel::INFO : $level;
        $this->maskSensitiveHeaders = $maskSensitiveHeaders;
        $this->requestConfig = $requestConfig;
        $this->responseConfig = $responseConfig;
    }

    public function getLogger(): AbstractLogger
    {
        return $this->logger;
    }

    /**
     * Getter for level of logging. See Psr\Log\LogLevel.php for possible values of log levels.
     * @return string
     */
    public function getLevel(): string
    {
        return $this->level;
    }

    public function shouldMaskSensitiveHeaders(): bool
    {
        return $this->maskSensitiveHeaders;
    }

    public function getRequestConfig(): LoggingRequestConfigInterface
    {
        return $this->requestConfig;
    }

    public function getResponseConfig(): LoggingResponseConfigInterface
    {
        return $this->responseConfig;
    }
}
