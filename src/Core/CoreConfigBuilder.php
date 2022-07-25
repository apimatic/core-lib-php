<?php

declare(strict_types=1);

namespace CoreLib\Core;

use CoreDesign\Core\Authentication\AuthInterface;
use CoreDesign\Http\HttpClientInterface;
use CoreDesign\Sdk\ConverterInterface;

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

    private function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
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
        return new CoreConfig($this->httpClient, $this->converter, $this->authManagers);
    }
}
