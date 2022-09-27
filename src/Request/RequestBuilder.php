<?php

declare(strict_types=1);

namespace Core\Request;

use Core\Authentication\Auth;
use Core\Client;
use Core\Utils\CoreHelper;
use Core\Utils\XmlSerializer;
use CoreInterfaces\Core\Format;
use CoreInterfaces\Core\Request\ParamInterface;
use CoreInterfaces\Http\RetryOption;

class RequestBuilder
{
    public static function init(string $requestMethod, string $path): self
    {
        return new self($requestMethod, $path);
    }

    private $requestMethod;
    private $path;

    /**
     * @var string|null
     */
    private $server;

    private $retryOption = RetryOption::USE_GLOBAL_SETTINGS;
    private $allowContentType = true;

    /**
     * @var ParamInterface[]
     */
    private $parameters = [];

    /**
     * @var callable
     */
    private $bodySerializer = [CoreHelper::class, 'serialize'];
    private $bodyFormat = Format::JSON;

    /**
     * @var Auth|null
     */
    private $auth;

    private function __construct(string $requestMethod, string $path)
    {
        $this->requestMethod = $requestMethod;
        $this->path = $path;
    }

    public function server(string $server): self
    {
        $this->server = $server;
        return $this;
    }

    public function retryOption(string $retryOption): self
    {
        $this->retryOption = $retryOption;
        return $this;
    }

    public function disableContentType(): self
    {
        $this->allowContentType = false;
        return $this;
    }

    /**
     * @param Auth|string ...$auths
     * @return $this
     */
    public function auth(...$auths): self
    {
        $this->auth = Auth::or(...$auths);
        return $this;
    }

    public function parameters(ParamInterface ...$parameters): self
    {
        $this->parameters = array_merge($this->parameters, $parameters);
        return $this;
    }

    public function bodyXml(string $rootName): self
    {
        $this->bodyFormat = Format::XML;
        $this->bodySerializer = function ($value) use ($rootName): string {
            return (new XmlSerializer([]))->serialize($rootName, $value);
        };
        return $this;
    }

    public function bodyXmlArray(string $rootName, string $itemName): self
    {
        $this->bodyFormat = Format::XML;
        $this->bodySerializer = function ($value) use ($rootName, $itemName): string {
            return (new XmlSerializer([]))->serializeArray($rootName, $itemName, $value);
        };
        return $this;
    }

    public function bodyXmlMap(string $rootName): self
    {
        $this->bodyFormat = Format::XML;
        $this->bodySerializer = function ($value) use ($rootName): string {
            return (new XmlSerializer([]))->serializeMap($rootName, $value);
        };
        return $this;
    }

    public function build(Client $coreClient): Request
    {
        $request = $coreClient->getGlobalRequest($this->server);
        $request->appendPath($this->path);
        $request->setHttpMethod($this->requestMethod);
        $request->setRetryOption($this->retryOption);
        $request->shouldAddContentType($this->allowContentType);
        $this->parameters = array_map(function ($param) use ($coreClient, $request) {
            $param->validate(Client::getJsonHelper($coreClient));
            $param->apply($request);
            return $param;
        }, array_merge($this->parameters, $coreClient->getGlobalRuntimeConfig()));
        if (isset($this->auth)) {
            $coreClient->validateAuth($this->auth)->apply($request);
        }
        $request->setBodyFormat($this->bodyFormat, $this->bodySerializer);
        return $request;
    }
}