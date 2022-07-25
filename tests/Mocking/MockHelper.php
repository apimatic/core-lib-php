<?php

namespace CoreLib\Tests\Mocking;

use CoreLib\Core\CoreConfig;
use CoreLib\Core\CoreConfigBuilder;
use CoreLib\Tests\Mocking\Authentication\FormAuthManager;
use CoreLib\Tests\Mocking\Authentication\HeaderAuthManager;
use CoreLib\Tests\Mocking\Authentication\QueryAuthManager;
use CoreLib\Tests\Mocking\Core\MockConverter;
use CoreLib\Tests\Mocking\Core\MockHttpClient;
use CoreLib\Tests\Mocking\Core\Response\MockResponse;

class MockHelper
{
    /**
     * @var CoreConfig
     */
    private static $coreConfig;

    /**
     * @var MockResponse
     */
    private static $response;

    public static function getCoreConfig(): CoreConfig
    {
        if (!isset(self::$coreConfig)) {
            self::$coreConfig = CoreConfigBuilder::init(new MockHttpClient())
                ->converter(new MockConverter())
                ->authManagers([
                    "header" => new HeaderAuthManager('someAuthToken', 'accessToken'),
                    "headerWithNull" => new HeaderAuthManager('someAuthToken', null),
                    "query" => new QueryAuthManager('someAuthToken', 'accessToken'),
                    "queryWithNull" => new QueryAuthManager(null, 'accessToken'),
                    "form" => new FormAuthManager('someAuthToken', 'accessToken'),
                    "formWithNull" => new FormAuthManager('newAuthToken', null)
                ])
                ->build();
        }
        return self::$coreConfig;
    }

    public static function getResponse(): MockResponse
    {
        if (!isset(self::$response)) {
            self::$response = new MockResponse();
        }
        return self::$response;
    }
}
