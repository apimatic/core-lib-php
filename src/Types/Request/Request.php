<?php

declare(strict_types=1);

namespace CoreLib\Types\Request;

use CoreDesign\Core\Request\RequestInterface;

class Request implements RequestInterface
{
    private $httpMethod;
    private $queryUrl;
    private $headers = [];
    private $body;
    private $hasFormParams = false;

    /**
     * @param string $httpMethod
     * @param string $queryUrl
     */
    public function __construct(string $httpMethod, string $queryUrl)
    {
        $this->httpMethod = $httpMethod;
        $this->queryUrl = $queryUrl;
    }

    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    public function getQueryUrl(): string
    {
        return $this->queryUrl;
    }

    public function setQueryUrl(string $queryUrl): void
    {
        $this->queryUrl = $queryUrl;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Add or replace a single header
     *
     * @param string $key  key for the header
     * @param mixed $value value of the header
     */
    public function addHeader(string $key, $value): void
    {
        $this->headers[$key] = $value;
    }

    public function getParameters(): array
    {
        return ($this->hasFormParams && is_array($this->body)) ? $this->body : [];
    }

    /**
     * Add or replace a single form parameter
     *
     * @param string $key  key for the parameter
     * @param mixed $value value of the parameter
     */
    public function addParameter(string $key, $value): void
    {
        if ($this->hasFormParams && is_array($this->body)) {
            $this->body[$key] = $value;
        } else {
            $this->hasFormParams = true;
            $this->body = [$key => $value];
        }
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body): void
    {
        $this->hasFormParams = false;
        $this->body = $body;
    }
}
