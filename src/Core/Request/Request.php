<?php

declare(strict_types=1);

namespace CoreLib\Core\Request;

use Closure;
use CoreDesign\Core\Format;
use CoreDesign\Core\Request\RequestMethod;
use CoreDesign\Core\Request\RequestSetterInterface;
use CoreDesign\Http\RetryOption;
use CoreLib\Core\CoreClient;
use CoreLib\Types\Sdk\CoreFileWrapper;
use CoreLib\Utils\CoreHelper;

class Request implements RequestSetterInterface
{
    private $converter;
    private $queryUrl;
    private $requestMethod = RequestMethod::GET;
    private $headers = [];
    private $parameters = [];
    private $body;
    private $retryOption = RetryOption::USE_GLOBAL_SETTINGS;

    /**
     * @param string $queryUrl
     * @param CoreClient|null $client
     */
    public function __construct(string $queryUrl, ?CoreClient $client = null)
    {
        $this->queryUrl = CoreHelper::validateUrl($queryUrl);
        $this->converter = CoreClient::getConverter($client);
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
        return $this->body;
    }

    public function getRetryOption(): string
    {
        return $this->retryOption;
    }

    public function convert()
    {
        return $this->converter->createHttpRequest($this);
    }

    public function toApiException(string $message)
    {
        return $this->converter->createApiException($message, $this, null);
    }

    public function addAcceptHeader(string $accept): void
    {
        if ($accept !== Format::SCALAR) {
            $this->addHeader('Accept', $accept);
        }
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

    public function setBodyFormat(string $format, callable $serializer): void
    {
        if (!empty($this->parameters)) {
            // if request contains form parameters then remove content-type
            if (array_key_exists('content-type', $this->headers)) {
                unset($this->headers['content-type']);
            }
            return;
        }
        if (!array_key_exists('content-type', $this->headers)) {
            // if request has body, and content-type header is not already added
            // then add content-type, based on type and format of body
            if ($this->body instanceof CoreFileWrapper) {
                $this->addHeader('content-type', 'application/octet-stream');
            } elseif ($format == Format::JSON && !is_object($this->body) && !is_array($this->body)) {
                $this->addHeader('content-type', Format::SCALAR);
            } else {
                $this->addHeader('content-type', $format);
            }
        }
        $this->body = Closure::fromCallable($serializer)($this->body);
    }

    public function setRetryOption(string $retryOption): void
    {
        $this->retryOption = $retryOption;
    }
}
