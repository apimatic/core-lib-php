<?php

namespace Core\Logger;

use Core\Logger\Configuration\LoggingConfig;
use CoreInterfaces\Core\Logger\ApiLoggerInterface;
use CoreInterfaces\Core\Request\RequestInterface;
use CoreInterfaces\Core\Response\ResponseInterface;
use Psr\Log\LogLevel;

class ApiLogger implements ApiLoggerInterface
{
    private $config;

    public function __construct(LoggingConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Log the provided request.
     *
     * @param $request RequestInterface HTTP requests to be logged.
     */
    public function logRequest(RequestInterface $request): void
    {
        $contentType = $this->getHeaderValue(LoggerConstants::CONTENT_TYPE_HEADER, $request->getHeaders());

        $this->logMessage(
            'Request {' . LoggerConstants::METHOD . '} {' . LoggerConstants::URL .
            '} {' . LoggerConstants::CONTENT_TYPE . '}',
            [
                LoggerConstants::METHOD => $request->getHttpMethod(),
                LoggerConstants::URL => $this->getRequestUrl($request),
                LoggerConstants::CONTENT_TYPE => $contentType
            ]
        );

        if ($this->config->getRequestConfig()->shouldLogHeaders()) {
            $headers = $this->config->getRequestConfig()->getLoggableHeaders(
                $request->getHeaders(),
                $this->config->shouldMaskSensitiveHeaders()
            );
            $this->logMessage(
                'Request Headers {' . LoggerConstants::HEADERS . '}',
                [LoggerConstants::HEADERS => $headers]
            );
        }

        if ($this->config->getRequestConfig()->shouldLogBody()) {
            $body = $request->getParameters();
            if (empty($body)) {
                $body = $request->getBody();
            }
            $this->logMessage(
                'Request Body {' . LoggerConstants::BODY . '}',
                [LoggerConstants::BODY => $body]
            );
        }
    }

    /**
     * Log the provided response.
     *
     * @param $response ResponseInterface HTTP responses to be logged.
     */
    public function logResponse(ResponseInterface $response): void
    {
        $contentLength = $this->getHeaderValue(LoggerConstants::CONTENT_LENGTH_HEADER, $response->getHeaders());
        $contentType = $this->getHeaderValue(LoggerConstants::CONTENT_TYPE_HEADER, $response->getHeaders());

        $this->logMessage(
            'Response {' . LoggerConstants::STATUS_CODE . '} {' . LoggerConstants::CONTENT_LENGTH .
            '} {' . LoggerConstants::CONTENT_TYPE . '}',
            [
                LoggerConstants::STATUS_CODE => $response->getStatusCode(),
                LoggerConstants::CONTENT_LENGTH => $contentLength,
                LoggerConstants::CONTENT_TYPE => $contentType
            ]
        );

        if ($this->config->getResponseConfig()->shouldLogHeaders()) {
            $headers = $this->config->getResponseConfig()->getLoggableHeaders(
                $response->getHeaders(),
                $this->config->shouldMaskSensitiveHeaders()
            );
            $this->logMessage(
                'Response Headers {' . LoggerConstants::HEADERS . '}',
                [LoggerConstants::HEADERS => $headers]
            );
        }

        if ($this->config->getResponseConfig()->shouldLogBody()) {
            $this->logMessage(
                'Response Body {' . LoggerConstants::BODY . '}',
                [LoggerConstants::BODY => $response->getBody()]
            );
        }
    }

    private function logMessage(string $message, array $context): void
    {
        switch ($this->config->getLevel()) {
            case LogLevel::DEBUG:
                $this->config->getLogger()->debug($message, $context);
                break;
            case LogLevel::INFO:
                $this->config->getLogger()->info($message, $context);
                break;
            case LogLevel::NOTICE:
                $this->config->getLogger()->notice($message, $context);
                break;
            case LogLevel::WARNING:
                $this->config->getLogger()->warning($message, $context);
                break;
            case LogLevel::ERROR:
                $this->config->getLogger()->error($message, $context);
                break;
            case LogLevel::CRITICAL:
                $this->config->getLogger()->critical($message, $context);
                break;
            case LogLevel::ALERT:
                $this->config->getLogger()->alert($message, $context);
                break;
            case LogLevel::EMERGENCY:
                $this->config->getLogger()->emergency($message, $context);
                break;
        }
    }

    private function getHeaderValue(string $key, array $headers): ?string
    {
        $key = strtolower($key);
        foreach ($headers as $k => $value) {
            if (strtolower($k) === $key) {
                return $value;
            }
        }
        return null;
    }

    private function getRequestUrl(RequestInterface $request): string
    {
        $queryUrl = $request->getQueryUrl();
        if ($this->config->getRequestConfig()->shouldIncludeQueryInPath()) {
            return $queryUrl;
        }
        return explode("?", $queryUrl)[0];
    }
}
