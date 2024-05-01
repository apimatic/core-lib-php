<?php

namespace Core\Logger;

use CoreInterfaces\Core\Logger\ApiLoggerInterface;
use CoreInterfaces\Core\Request\RequestInterface;
use CoreInterfaces\Core\Response\ResponseInterface;

class ApiLogger implements ApiLoggerInterface
{
    private $loggingConfiguration;

    public function __construct(LoggingConfiguration $loggingConfiguration)
    {
        $this->loggingConfiguration = $loggingConfiguration;
    }

    /**
     * Log the provided request.
     *
     * @param $request RequestInterface HTTP requests to be logged.
     */
    public function logRequest(RequestInterface $request): void
    {
        $this->logMessage($request->getQueryUrl(), []);
        // TODO: Implement logRequest() method.
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
        $this->loggingConfiguration->getLogger()->log(
            $this->loggingConfiguration->getLevel(),
            $message,
            $context
        );
    }
}
