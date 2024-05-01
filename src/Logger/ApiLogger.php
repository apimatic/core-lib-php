<?php

namespace Core\Logger;

use Core\Logger\Configuration\LoggingConfig;
use Core\Utils\CoreHelper;
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
        $contentType = $this->findHeaderIgnoringCase(LoggerConstants::CONTENT_TYPE_HEADER, $request->getHeaders());

        $requestContext = [
            LoggerConstants::METHOD => $request->getHttpMethod(),
            LoggerConstants::URL => $this->getRequestUrl($request),
            LoggerConstants::CONTENT_TYPE => $contentType
        ];

        $this->logMessage("Request %s %s %s", $requestContext);

        if ($this->config->getRequestConfig()->shouldLogHeaders()) {
            $this->logMessage("Request Headers %s", [LoggerConstants::HEADERS => $request->getHeaders()]);
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
        $this->logMessage($response->getRawBody(), []);
        // TODO: Implement logResponse() method.
    }

    private function logMessage(string $message, array $context): void
    {
        $this->config->getLogger()->log(
            $this->config->getLevel(),
            $message,
            $context
        );
    }

    private function findHeaderIgnoringCase(string $key, array $headers): ?string
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
