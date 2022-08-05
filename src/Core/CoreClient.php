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
use CoreLib\Core\Response\ResponseHandler;
use CoreLib\Core\Response\Types\ErrorType;
use CoreLib\Types\Sdk\CoreCallback;
use CoreLib\Utils\JsonHelper;

class CoreClient
{
    private static $converter;
    private static $jsonHelper;
    public static function getConverter(CoreClient $client = null): ConverterInterface
    {
        if (isset($client)) {
            return $client->localConverter;
        }
        return self::$converter;
    }
    public static function getJsonHelper(CoreClient $client = null): JsonHelper
    {
        if (isset($client)) {
            return $client->localJsonHelper;
        }
        return self::$jsonHelper;
    }

    private $httpClient;
    private $localConverter;
    private $localJsonHelper;
    private $authManagers;
    private $serverUrls;
    private $defaultServer;
    private $globalConfig;
    private $globalErrors;
    private $apiCallback;

    /**
     * @param HttpClientInterface $httpClient
     * @param ConverterInterface $converter
     * @param JsonHelper $jsonHelper
     * @param array<string,AuthInterface> $authManagers
     * @param array<string,string> $serverUrls
     * @param string $defaultServer
     * @param ParamInterface[] $globalConfig
     * @param array<int,ErrorType> $globalErrors
     * @param CoreCallback|null $apiCallback
     */
    public function __construct(
        HttpClientInterface $httpClient,
        ConverterInterface $converter,
        JsonHelper $jsonHelper,
        array $authManagers,
        array $serverUrls,
        string $defaultServer,
        array $globalConfig,
        array $globalErrors,
        ?CoreCallback $apiCallback
    ) {
        $this->httpClient = $httpClient;
        self::$converter = $converter;
        $this->localConverter = $converter;
        self::$jsonHelper = $jsonHelper;
        $this->localJsonHelper = $jsonHelper;
        $this->authManagers = $authManagers;
        $this->serverUrls = $serverUrls;
        $this->defaultServer = $defaultServer;
        $this->globalConfig = $globalConfig;
        $this->globalErrors = $globalErrors;
        $this->apiCallback = $apiCallback;
    }

    public function getGlobalRequest(?string $server = null): Request
    {
        $request = new Request($this->serverUrls[$server ?? $this->defaultServer]);
        foreach ($this->globalConfig as $config) {
            $config->validate(self::getJsonHelper($this));
            $config->apply($request);
        }
        return $request;
    }

    public function getGlobalResponseHandler(): ResponseHandler
    {
        $responseHandler = new ResponseHandler();
        foreach ($this->globalErrors as $key => $error) {
            $responseHandler->throwErrorOn($key, $error);
        }
        return $responseHandler;
    }

    public function getHttpClient(): HttpClientInterface
    {
        return $this->httpClient;
    }

    public function validateAuth(Auth $auth): Auth
    {
        $auth->withAuthManagers($this->authManagers)->validate(self::getJsonHelper($this));
        return $auth;
    }

    public function beforeRequest(Request $request)
    {
        if (isset($this->apiCallback)) {
            $this->apiCallback->callOnBeforeWithConversion($request, self::getConverter($this));
        }
    }

    public function afterResponse(Context $context)
    {
        if (isset($this->apiCallback)) {
            $this->apiCallback->callOnAfterWithConversion($context, self::getConverter($this));
        }
    }
}
