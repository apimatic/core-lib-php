<?php

namespace CoreLib\Core;

use CoreLib\Types\DefaultConfigurations;

class CoreConfig
{
    public static function init(DefaultConfigurations $config): self
    {
        return new CoreConfig($config);
    }

    /**
     * @var DefaultConfigurations
     */
    private $defaultConfig;

    private function __construct(DefaultConfigurations $config)
    {
        $this->defaultConfig = $config;
    }

    /**
     * Get the default configurations.
     */
    public function getDefaultConfig(): DefaultConfigurations
    {
        return $this->defaultConfig;
    }
}
