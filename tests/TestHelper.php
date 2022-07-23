<?php

namespace CoreLib\Tests;

use CoreLib\Core\CoreConfig;
use CoreLib\Core\CoreConfigBuilder;
use CoreLib\Tests\Mocking\Core\MockClient;
use CoreLib\Tests\Mocking\Core\MockConverter;
use CoreLib\Tests\Mocking\Core\MockHttpClient;

class TestHelper
{
    /**
     * @var CoreConfig
     */
    private static $mockCoreConfig;

    public static function getMockCoreConfig(): CoreConfig
    {
        if (!isset(self::$mockCoreConfig)) {
            self::$mockCoreConfig = CoreConfigBuilder::init(new MockClient())
                ->converter(new MockConverter())
                ->httpClient(new MockHttpClient())
                ->build();
        }
        return self::$mockCoreConfig;
    }
}
