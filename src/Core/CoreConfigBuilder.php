<?php

declare(strict_types=1);

namespace CoreLib\Core;

use CoreDesign\Core\Authentication\AuthInterface;
use CoreDesign\Core\Request\ParamInterface;
use CoreDesign\Http\HttpClientInterface;
use CoreDesign\Sdk\ConverterInterface;
use CoreLib\Core\Request\Parameters\HeaderParam;
use CoreLib\Core\Response\ErrorType;
use CoreLib\Core\Response\ResponseError;
use CoreLib\Types\Sdk\CoreCallback;
use CoreLib\Utils\JsonHelper;

class CoreConfigBuilder
{
    public static function init(HttpClientInterface $httpClient): self
    {
        return new CoreConfigBuilder($httpClient);
    }

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var ConverterInterface
     */
    private $converter;

    /**
     * @var array<string,AuthInterface>
     */
    private $authManagers = [];

    /**
     * @var ResponseError
     */
    private $globalResponseError;

    /**
     * @var array<string,string>
     */
    private $serverUrls = [];

    /**
     * @var string|null
     */
    private $defaultServer;

    /**
     * @var ParamInterface[]
     */
    private $globalConfig = [];

    /**
     * @var CoreCallback|null
     */
    private $apiCallback;

    /**
     * @var string|null
     */
    private $userAgent;

    /**
     * @var array<string,string>
     */
    private $userAgentConfig = [];

    /**
     * @var array<string,string[]>
     */
    private $inheritedModels = [];

    /**
     * @var string|null
     */
    private $additionalPropMethodName;

    /**
     * @var string|null
     */
    private $modelNamespace;

    private function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function converter(ConverterInterface $converter): self
    {
        $this->converter = $converter;
        return $this;
    }

    /**
     * @param array<string,AuthInterface> $authManagers
     * @return $this
     */
    public function authManagers(array $authManagers): self
    {
        $this->authManagers = $authManagers;
        return $this;
    }

    /**
     * @param array<int,ErrorType> $globalErrors
     * @return $this
     */
    public function globalErrors(array $globalErrors): self
    {
        $this->globalResponseError = new ResponseError($globalErrors);
        return $this;
    }

    /**
     * @param array<string,string> $serverUrls
     * @return $this
     */
    public function serverUrls(array $serverUrls, string $defaultServer): self
    {
        $this->serverUrls = $serverUrls;
        $this->defaultServer = $defaultServer;
        return $this;
    }

    public function apiCallback(CoreCallback $apiCallback): self
    {
        $this->apiCallback = $apiCallback;
        return $this;
    }

    public function globalConfig(ParamInterface ...$globalParams): self
    {
        $this->globalConfig = $globalParams;
        return $this;
    }

    public function userAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    /**
     * @param array<string,string> $userAgentConfig
     * @return $this
     */
    public function userAgentConfig(array $userAgentConfig): self
    {
        $this->userAgentConfig = $userAgentConfig;
        return $this;
    }

    /**
     * @param array<string,string[]> $inheritedModels
     * @return $this
     */
    public function inheritedModels(array $inheritedModels): self
    {
        $this->inheritedModels = $inheritedModels;
        return $this;
    }

    public function additionalPropertiesMethodName(string $additionalPropertiesMethodName): self
    {
        $this->additionalPropMethodName = $additionalPropertiesMethodName;
        return $this;
    }

    public function modelNamespace(string $modelNamespace): self
    {
        $this->modelNamespace = $modelNamespace;
        return $this;
    }

    private function addUserAgentToGlobalHeaders(): void
    {
        if (is_null($this->userAgent)) {
            return;
        }
        $placeHolders = [
            '{engine}' => !empty(zend_version()) ? 'Zend' : '',
            '{engine-version}' => zend_version(),
            '{os-info}' => PHP_OS_FAMILY !== 'Unknown' ? PHP_OS_FAMILY . '-' . php_uname('r') : '',
        ];
        $placeHolders = array_merge($placeHolders, $this->userAgentConfig);
        $this->userAgent = str_replace(
            array_keys($placeHolders),
            array_values($placeHolders),
            $this->userAgent
        );
        $this->globalConfig[] = HeaderParam::init('user-agent', $this->userAgent);
        $this->userAgent = null;
    }

    public function build(): CoreConfig
    {
        $this->addUserAgentToGlobalHeaders();
        $jsonHelper = new JsonHelper($this->inheritedModels, $this->additionalPropMethodName, $this->modelNamespace);
        return new CoreConfig(
            $this->httpClient,
            $this->converter,
            $this->authManagers,
            $this->serverUrls,
            $this->defaultServer,
            $this->globalConfig,
            $this->globalResponseError,
            $this->apiCallback,
            $jsonHelper
        );
    }
}
