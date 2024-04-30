<?php

declare(strict_types=1);

namespace Core\Logger;

use CoreInterfaces\Core\Logger\LoggingRequestConfigInterface;

class LoggingRequestConfiguration implements LoggingRequestConfigInterface
{
    private $includeQueryInPath;
    private $logBody;
    private $logHeaders;
    private $headersToInclude;
    private $headersToExclude;

    public function __construct(
        bool $includeQueryInPath,
        bool $logBody,
        bool $logHeaders,
        array $headersToInclude,
        array $headersToExclude
    ) {
        $this->includeQueryInPath = $includeQueryInPath;
        $this->logBody = $logBody;
        $this->logHeaders = $logHeaders;
        $this->headersToInclude = $headersToInclude;
        $this->headersToExclude = $headersToExclude;
    }

    public function shouldIncludeQueryInPath(): bool
    {
        return $this->includeQueryInPath;
    }

    public function shouldLogBody(): bool
    {
        return $this->logBody;
    }

    public function shouldLogHeaders(): bool
    {
        return $this->logHeaders;
    }

    /**
     * Gets the list of headers to include in logging.
     *
     * @return string[]
     */
    public function getHeadersToInclude(): array
    {
        return $this->headersToInclude;
    }

    /**
     * Gets the list of headers to exclude from logging.
     *
     * @return string[]
     */
    public function getHeadersToExclude(): array
    {
        return $this->headersToExclude;
    }
}
