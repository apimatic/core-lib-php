<?php

declare(strict_types=1);

namespace Core\Logger\Configuration;

use Core\Logger\ConsoleLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

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
     * Log the given message using the context array. This function uses the
     * LogLevel and Logger instance set via constructor of this class.
     */
    public function logMessage(string $message, array $context): void
    {
        switch ($this->level) {
            case LogLevel::DEBUG:
                $this->logger->debug($message, $context);
                break;
            case LogLevel::INFO:
                $this->logger->info($message, $context);
                break;
            case LogLevel::NOTICE:
                $this->logger->notice($message, $context);
                break;
            case LogLevel::WARNING:
                $this->logger->warning($message, $context);
                break;
            case LogLevel::ERROR:
                $this->logger->error($message, $context);
                break;
            case LogLevel::CRITICAL:
                $this->logger->critical($message, $context);
                break;
            case LogLevel::ALERT:
                $this->logger->alert($message, $context);
                break;
            case LogLevel::EMERGENCY:
                $this->logger->emergency($message, $context);
                break;
        }
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
