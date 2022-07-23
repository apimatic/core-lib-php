<?php

declare(strict_types=1);

namespace CoreLib\Core;

use CoreDesign\Core\Authentication\AuthInterface;
use CoreDesign\Http\HttpClientInterface;
use CoreDesign\Http\HttpConfigurations;
use CoreDesign\Sdk\ConverterInterface;

class CoreConfig
{
    private $httpConfigurations;
    private $httpClient;
    private $converter;
    private $authManagers;

    /**
     * @param HttpConfigurations $config
     * @param HttpClientInterface $httpClient
     * @param ConverterInterface $converter
     * @param array<string, AuthInterface> $authManagers
     */
    public function __construct(
        HttpConfigurations $config,
        HttpClientInterface $httpClient,
        ConverterInterface $converter,
        array $authManagers
    ) {
        $this->httpConfigurations = $config;
        $this->httpClient = $httpClient;
        $this->converter = $converter;
        $this->authManagers = $authManagers;
    }

    public function getHttpConfigurations(): HttpConfigurations
    {
        return $this->httpConfigurations;
    }

    public function getHttpClient(): HttpClientInterface
    {
        return $this->httpClient;
    }

    public function getConverter(): ConverterInterface
    {
        return $this->converter;
    }

    public function getAuthManagers(): array
    {
        return $this->authManagers;
    }
}
