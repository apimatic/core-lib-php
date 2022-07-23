<?php

declare(strict_types=1);

namespace CoreLib\Core;

use CoreDesign\Core\Authentication\AuthInterface;
use CoreDesign\Http\HttpClientInterface;
use CoreDesign\Http\HttpConfigurations;
use CoreDesign\Sdk\ConverterInterface;

class CoreConfigBuilder
{
    public static function init(HttpConfigurations $config): self
    {
        return new CoreConfigBuilder($config);
    }

    /**
     * @var HttpConfigurations
     */
    private $config;

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

    private function __construct(HttpConfigurations $config)
    {
        $this->config = $config;
    }

    public function httpClient(HttpClientInterface $httpClient): self
    {
        $this->httpClient = $httpClient;
        return $this;
    }

    public function converter(ConverterInterface $converter): self
    {
        $this->converter = $converter;
        return $this;
    }

    public function authManagers(array $authManagers): self
    {
        $this->authManagers = $authManagers;
        return $this;
    }

    public function build(): CoreConfig
    {
        return new CoreConfig($this->config, $this->httpClient, $this->converter, $this->authManagers);
    }
}
