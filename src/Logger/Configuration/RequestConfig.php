<?php

declare(strict_types=1);

namespace Core\Logger\Configuration;

class RequestConfig extends ResponseConfig
{
    private $includeQueryInPath;

    /**
     * Construct an instance of RequestConfig for logging
     *
     * @param bool $includeQueryInPath
     * @param bool $logBody
     * @param bool $logHeaders
     * @param string[] $headersToInclude
     * @param string[] $headersToExclude
     * @param string[] $headersToUnmask
     */
    public function __construct(
        bool $includeQueryInPath,
        bool $logBody,
        bool $logHeaders,
        array $headersToInclude,
        array $headersToExclude,
        array $headersToUnmask
    ) {
        parent::__construct(
            $logBody,
            $logHeaders,
            $headersToInclude,
            $headersToExclude,
            $headersToUnmask
        );
        $this->includeQueryInPath = $includeQueryInPath;
    }

    /**
     * Indicates whether to include query parameters in the logged path.
     *
     * @return bool
     */
    public function shouldIncludeQueryInPath(): bool
    {
        return $this->includeQueryInPath;
    }
}
