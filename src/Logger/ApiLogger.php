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

        $requestArguments = [
            LoggerConstants::METHOD => $request->getHttpMethod(),
            LoggerConstants::URL => $this->getRequestUrl($request),
            LoggerConstants::CONTENT_TYPE => $contentType
        ];

        $this->logMessage("Request %s %s %s", $requestArguments);

        if ($this->config->getRequestConfig()->shouldLogHeaders()) {
            $requestHeaderArguments = $request->getHeaders();
            $this->logMessage("Request Headers %s", [LoggerConstants::HEADERS => $requestHeaderArguments]);
        }

        if ($this->config->getRequestConfig()->shouldLogBody()) {
            $body = $request->getParameters();
            if (empty($body)) {
                $body = $request->getBody();
            }
            $requestBodyArguments = [LoggerConstants::BODY => CoreHelper::serialize($body)];
            $this->logMessage("Request Body %s", $requestBodyArguments);
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
