<?php

declare(strict_types=1);

namespace Core\Logger\Configuration;

class RequestConfig extends ResponseConfig
{
    private $includeQueryInPath;

    public function __construct(
        bool $includeQueryInPath,
        bool $logBody,
        bool $logHeaders,
        array $headersToInclude,
        array $headersToExclude
    ) {
        parent::__construct(
            $logBody,
            $logHeaders,
            $headersToInclude,
            $headersToExclude
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
