<?php

declare(strict_types=1);

namespace Core\Logger;

use CoreInterfaces\Core\Logger\LoggingResponseConfigInterface;

class LoggingResponseConfiguration implements LoggingResponseConfigInterface
{
    private $logBody;
    private $logHeaders;
    private $headersToInclude;
    private $headersToExclude;

    public function __construct(
        bool $logBody,
        bool $logHeaders,
        array $headersToInclude,
        array $headersToExclude
    ) {
        $this->logBody = $logBody;
        $this->logHeaders = $logHeaders;
        $this->headersToInclude = $headersToInclude;
        $this->headersToExclude = $headersToExclude;
    }

    /**
     * Indicates whether to log the body.
     *
     * @return bool
     */
    public function shouldLogBody(): bool
    {
        return $this->logBody;
    }

    /**
     * Indicates whether to log the headers.
     *
     * @return bool
     */
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
