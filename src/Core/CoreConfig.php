<?php

declare(strict_types=1);

namespace CoreLib\Core;

use CoreDesign\Core\Authentication\AuthInterface;
use CoreDesign\Core\Request\ParamInterface;
use CoreDesign\Http\HttpClientInterface;
use CoreDesign\Sdk\ConverterInterface;
use CoreLib\Authentication\Auth;
use CoreLib\Core\Request\Request;
use CoreLib\Core\Response\Context;
use CoreLib\Core\Response\ResponseError;
use CoreLib\Types\Sdk\CoreCallback;
use CoreLib\Utils\JsonHelper;

class CoreConfig
{
    private $httpClient;
    private $converter;
    private $authManagers;
    private $serverUrls;
    private $defaultServer;
    private $globalConfig;
    private $globalResponseError;
    private $apiCallback;
    private $jsonHelper;

    /**
     * @param HttpClientInterface $httpClient
     * @param ConverterInterface $converter
     * @param array<string,AuthInterface> $authManagers
     * @param array<string,string> $serverUrls
     * @param string $defaultServer
     * @param ParamInterface[] $globalConfig
     * @param ResponseError $globalResponseError
     * @param CoreCallback|null $apiCallback
     * @param JsonHelper $jsonHelper
     */
    public function __construct(
        HttpClientInterface $httpClient,
        ConverterInterface $converter,
        array $authManagers,
        array $serverUrls,
        string $defaultServer,
        array $globalConfig,
        ResponseError $globalResponseError,
        ?CoreCallback $apiCallback,
        JsonHelper $jsonHelper
    ) {
        $this->httpClient = $httpClient;
        $this->converter = $converter;
        $this->authManagers = $authManagers;
        $this->serverUrls = $serverUrls;
        $this->defaultServer = $defaultServer;
        $this->globalConfig = $globalConfig;
        $this->globalResponseError = $globalResponseError;
        $this->apiCallback = $apiCallback;
        $this->jsonHelper = $jsonHelper;
    }

    public function getGlobalRequest(?string $server = null): Request
    {
        $request = new Request($this->serverUrls[$server ?? $this->defaultServer]);
        foreach ($this->globalConfig as $config) {
            $config->validate();
            $config->apply($request);
        }
        return $request;
    }

    public function getGlobalError(): ResponseError
    {
        return $this->globalResponseError;
    }

    public function getHttpClient(): HttpClientInterface
    {
        return $this->httpClient;
    }

    public function getConverter(): ConverterInterface
    {
        return $this->converter;
    }

    public function validateAuth(Auth $auth): Auth
    {
        $auth->withAuthManagers($this->authManagers)->validate();
        return $auth;
    }

    public function beforeRequest(Request $request)
    {
        if (isset($this->apiCallback)) {
            $this->apiCallback->callOnBeforeWithConversion($request, $this->converter);
        }
    }

    public function afterResponse(Context $context)
    {
        if (isset($this->apiCallback)) {
            $this->apiCallback->callOnAfterWithConversion($context, $this->converter);
        }
    }

    public function getJsonHelper(): JsonHelper
    {
        return $this->jsonHelper;
    }
}
