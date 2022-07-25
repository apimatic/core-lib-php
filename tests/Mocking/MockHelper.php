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
use CoreLib\Tests\Mocking\Types\MockCallback;
use CoreLib\Tests\Mocking\Types\MockFileWrapper;
use CoreLib\Types\CallbackCatcher;

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

    /**
     * @var MockCallback
     */
    private static $callback;

    /**
     * @var CallbackCatcher
     */
    private static $callbackCatcher;

    /**
     * @var MockFileWrapper
     */
    private static $fileWrapper;

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

    public static function getCallback(): MockCallback
    {
        if (!isset(self::$callback)) {
            self::$callback = new MockCallback();
        }
        return self::$callback;
    }

    public static function getCallbackCatcher(): CallbackCatcher
    {
        if (!isset(self::$callbackCatcher)) {
            self::$callbackCatcher = new CallbackCatcher();
        }
        return self::$callbackCatcher;
    }

    public static function getFileWrapper(): MockFileWrapper
    {
        if (!isset(self::$fileWrapper)) {
            $filePath = realpath(__DIR__ . '/Other/testFile.txt');
            self::$fileWrapper = MockFileWrapper::createFromPath($filePath, 'text/plain', 'My Text');
        }
        return self::$fileWrapper;
    }
}
