<?php

declare(strict_types=1);

namespace Core\Request;

use Closure;
use Core\Client;
use Core\Types\Sdk\CoreFileWrapper;
use Core\Utils\CoreHelper;
use CoreInterfaces\Core\Format;
use CoreInterfaces\Core\Request\RequestMethod;
use CoreInterfaces\Core\Request\RequestSetterInterface;
use CoreInterfaces\Http\RetryOption;

class Request implements RequestSetterInterface
{
    private $converter;
    private $queryUrl;
    private $requestMethod = RequestMethod::GET;
    private $headers = [];
    private $parameters = [];
    private $parametersEncoded = [];
    private $parametersMultipart = [];
    private $body;
    private $retryOption = RetryOption::USE_GLOBAL_SETTINGS;
    private $allowContentType = true;

    /**
     * @param string $queryUrl
     * @param Client|null $client
     */
    public function __construct(string $queryUrl, ?Client $client = null)
    {
        $this->queryUrl = CoreHelper::validateUrl($queryUrl);
        $this->converter = Client::getConverter($client);
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

    public function getEncodedParameters(): array
    {
        return $this->parametersEncoded;
    }

    public function getMultipartParameters(): array
    {
        return $this->parametersMultipart;
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
        if ($this->allowContentType && $accept !== Format::SCALAR) {
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

    public function addEncodedFormParam(string $key, $value, $realValue): void
    {
        $this->parametersEncoded[$key] = $value;
        $this->parameters[$key] = $realValue;
    }

    public function addMultipartFormParam(string $key, $value): void
    {
        $this->parametersMultipart[$key] = $value;
        $this->parameters[$key] = $value;
    }

    public function addBodyParam($value, string $key = ''): void
    {
        if (empty($key)) {
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
            return;
        }
        if ($this->allowContentType && !array_key_exists('content-type', array_change_key_case($this->headers))) {
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

    public function shouldAddContentType(bool $allowContentType): void
    {
        $this->allowContentType = $allowContentType;
    }
}
