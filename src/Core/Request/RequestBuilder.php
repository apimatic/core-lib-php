<?php

declare(strict_types=1);

namespace CoreLib\Core\Request;

use CoreDesign\Core\Format;
use CoreDesign\Core\Request\ParamInterface;
use CoreDesign\Http\RetryOption;
use CoreLib\Authentication\Auth;
use CoreLib\Core\CoreConfig;
use CoreLib\Utils\CoreHelper;
use CoreLib\Utils\XmlSerializer;

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

    /**
     * @var string
     */
    private $retryOption = RetryOption::USE_GLOBAL_SETTINGS;

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
        $this->parameters = $parameters;
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

    public function build(CoreConfig $coreConfig): Request
    {
        $request = $coreConfig->getGlobalRequest($this->server);
        $request->appendPath($this->path);
        $request->setHttpMethod($this->requestMethod);
        $request->setRetryOption($this->retryOption);
        foreach ($this->parameters as $param) {
            $param->validate(CoreConfig::getJsonHelper($coreConfig));
            $param->apply($request);
        }
        $request->setBodyFormat($this->bodyFormat, $this->bodySerializer);
        if (isset($this->auth)) {
            $coreConfig->validateAuth($this->auth)->apply($request);
        }
        return $request;
    }
}
