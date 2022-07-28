<?php

declare(strict_types=1);

namespace CoreLib\Core\Request;

use CoreDesign\Core\Format;
use CoreDesign\Core\Request\RequestMethod;
use CoreDesign\Core\Request\RequestSetterInterface;
use CoreDesign\Http\RetryOption;
use CoreDesign\Sdk\ConverterInterface;
use CoreLib\Utils\CoreHelper;
use CoreLib\Utils\XmlSerializer;

class Request implements RequestSetterInterface
{
    private static $xmlSerializer;
    private $queryUrl;
    private $requestMethod = RequestMethod::GET;
    private $headers = [];
    private $parameters = [];
    private $body;
    private $bodyFormat;
    private $xmlRootName;
    private $retryOption = RetryOption::USE_GLOBAL_SETTINGS;

    /**
     * @param string $queryUrl
     */
    public function __construct(string $queryUrl)
    {
        $this->queryUrl = $queryUrl;
    }

    public function getHttpMethod(): string
    {
        return $this->requestMethod;
    }

    public function getQueryUrl(): string
    {
        return $this->queryUrl;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getBody()
    {
        if ($this->bodyFormat == Format::JSON) {
            return CoreHelper::serialize($this->body);
        } elseif ($this->bodyFormat == Format::XML) {
            if (is_null(self::$xmlSerializer)) {
                self::$xmlSerializer = new XmlSerializer([]);
            }
            return self::$xmlSerializer->serialize($this->xmlRootName, $this->body);
        }
        return $this->body;
    }

    public function getRetryOption(): string
    {
        return $this->retryOption;
    }

    public function setHttpMethod(string $requestMethod): void
    {
        $this->requestMethod = $requestMethod;
    }

    public function appendPath(string $path): void
    {
        $this->queryUrl .= $path;
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

    public function addTemplate(string $key, $value): void
    {
        $this->queryUrl = str_replace("{{$key}}", $value, $this->queryUrl);
    }

    /**
     * Add or replace a single form parameter
     *
     * @param string $key  key for the parameter
     * @param mixed $value value of the parameter
     */
    public function addFormParam(string $key, $value, string $encodedBody): void
    {
        $this->bodyFormat = null;
        if (empty($this->parameters)) {
            $this->body = $encodedBody;
        } else {
            $this->body .= "&$encodedBody";
        }
        $this->parameters[$key] = $value;
    }

    public function addBodyParam($value, ?string $key = null): void
    {
        $this->parameters = [];
        if (is_null($key)) {
            $this->body = $value;
            return;
        }
        if (is_array($this->body)) {
            $this->body[$key] = $value;
        } else {
            $this->body = [$key => $value];
        }
    }

    public function setBodyAsXml(string $rootName): void
    {
        $this->bodyFormat = Format::XML;
        $this->xmlRootName = $rootName;
    }

    public function setBodyAsJson(): void
    {
        $this->bodyFormat = Format::JSON;
    }

    public function setRetryOption(string $retryOption): void
    {
        $this->retryOption = $retryOption;
    }

    public function convert(ConverterInterface $converter)
    {
        return $converter->createHttpRequest($this);
    }
}
