<?php

namespace Core\Logger;

use Core\Logger\Configuration\LoggingConfig;
use CoreInterfaces\Core\Logger\ApiLoggerInterface;
use CoreInterfaces\Core\Request\RequestInterface;
use CoreInterfaces\Core\Response\ResponseInterface;

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
        $contentType = $this->findHeader(LoggerConstants::CONTENT_TYPE_HEADER, $request->getHeaders());

        $this->logMessage("Request %s %s %s", [
            LoggerConstants::METHOD => $request->getHttpMethod(),
            LoggerConstants::URL => $this->getRequestUrl($request),
            LoggerConstants::CONTENT_TYPE => $contentType
        ]);

        if ($this->config->getRequestConfig()->shouldLogHeaders()) {
            $headers = $this->config->getRequestConfig()->getLoggableHeaders(
                $request->getHeaders(),
                $this->config->shouldMaskSensitiveHeaders()
            );
            $this->logMessage("Request Headers %s", [LoggerConstants::HEADERS => $headers]);
        }

        if ($this->config->getRequestConfig()->shouldLogBody()) {
            $body = $request->getParameters();
            if (empty($body)) {
                $body = $request->getBody();
            }
            $this->logMessage("Request Body %s", [LoggerConstants::BODY => $body]);
        }
    }

    /**
     * Log the provided response.
     *
     * @param $response ResponseInterface HTTP responses to be logged.
     */
    public function logResponse(ResponseInterface $response): void
    {
        $contentLength = $this->findHeader(LoggerConstants::CONTENT_LENGTH_HEADER, $response->getHeaders());
        $contentType = $this->findHeader(LoggerConstants::CONTENT_TYPE_HEADER, $response->getHeaders());

        $this->logMessage("Response %s %s %s", [
            LoggerConstants::STATUS_CODE => $response->getStatusCode(),
            LoggerConstants::CONTENT_LENGTH => $contentLength,
            LoggerConstants::CONTENT_TYPE => $contentType
        ]);

        if ($this->config->getResponseConfig()->shouldLogHeaders()) {
            $headers = $this->config->getResponseConfig()->getLoggableHeaders(
                $response->getHeaders(),
                $this->config->shouldMaskSensitiveHeaders()
            );
            $this->logMessage("Response Headers %s", [LoggerConstants::HEADERS => $headers]);
        }

        if ($this->config->getResponseConfig()->shouldLogBody()) {
            $this->logMessage("Response Body %s", [LoggerConstants::BODY => $response->getBody()]);
        }
    }

    private function logMessage(string $message, array $context): void
    {
        $this->config->getLogger()->log(
            $this->config->getLevel(),
            $message,
            $context
        );
    }

    private function findHeader(string $key, array $headers): ?string
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
