<?php

declare(strict_types=1);

namespace Core\Logger\Configuration;

use Core\Logger\LoggerConstants;

class HttpConfiguration
{
    private $logBody;
    private $logHeaders;
    private $headersToInclude;
    private $headersToExclude;
    private $headersToUnmask;

    /**
     * Construct an instance of ResponseConfig for logging
     *
     * @param bool $logBody
     * @param bool $logHeaders
     * @param string[] $headersToInclude
     * @param string[] $headersToExclude
     * @param string[] $headersToUnmask
     */
    public function __construct(
        bool $logBody,
        bool $logHeaders,
        array $headersToInclude,
        array $headersToExclude,
        array $headersToUnmask
    ) {
        $this->logBody = $logBody;
        $this->logHeaders = $logHeaders;
        $this->headersToInclude = array_map('strtolower', $headersToInclude);
        $this->headersToExclude = array_map('strtolower', $headersToExclude);
        $this->headersToUnmask = array_merge(
            array_map('strtolower', LoggerConstants::NON_SENSITIVE_HEADERS),
            array_map('strtolower', $headersToUnmask)
        );
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
     * Select the headers from the list of provided headers for logging.
     *
     * @param string[] $headers
     * @param bool $maskSensitiveHeaders
     *
     * @return string[]
     */
    public function getLoggableHeaders(array $headers, bool $maskSensitiveHeaders): array
    {
        $headersAfterInclusion = [];
        $headersAfterExclusion = [];
        $filteredHeaders = [];
        foreach ($headers as $key => $value) {
            $lowerCaseKey = strtolower(strval($key));
            if ($maskSensitiveHeaders && $this->isSensitiveHeader($lowerCaseKey)) {
                $value = '**Redacted**';
            }
            if (in_array($lowerCaseKey, $this->headersToInclude)) {
                $headersAfterInclusion[$key] = $value;
            }
            if (!in_array($lowerCaseKey, $this->headersToExclude)) {
                $headersAfterExclusion[$key] = $value;
            }
            $filteredHeaders[$key] = $value;
        }
        if (!empty($this->headersToInclude)) {
            return $headersAfterInclusion;
        }
        if (!empty($this->headersToExclude)) {
            return $headersAfterExclusion;
        }
        return $filteredHeaders;
    }

    private function isSensitiveHeader($headerKey): bool
    {
        if (in_array($headerKey, $this->headersToUnmask, true)) {
            return false;
        }
        return true;
    }
}
