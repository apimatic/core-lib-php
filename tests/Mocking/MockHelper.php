<?php

namespace CoreLib\Tests\Mocking;

use CoreLib\Core\CoreConfig;
use CoreLib\Core\CoreConfigBuilder;
use CoreLib\Core\Request\Parameters\HeaderParam;
use CoreLib\Core\Request\Parameters\TemplateParam;
use CoreLib\Tests\Mocking\Authentication\FormAuthManager;
use CoreLib\Tests\Mocking\Authentication\HeaderAuthManager;
use CoreLib\Tests\Mocking\Authentication\QueryAuthManager;
use CoreLib\Tests\Mocking\Core\MockConverter;
use CoreLib\Tests\Mocking\Core\MockHttpClient;
use CoreLib\Tests\Mocking\Core\Response\MockResponse;
use CoreLib\Tests\Mocking\Other\MockChild1;
use CoreLib\Tests\Mocking\Other\MockChild2;
use CoreLib\Tests\Mocking\Other\MockClass;
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
            $coreConfigBuilder = CoreConfigBuilder::init(new MockHttpClient())
                ->converter(new MockConverter())
                ->apiCallback(self::getCallbackCatcher())
                ->serverUrls([
                    'Server1' => 'my/path/{one}',
                    'Server2' => 'my/path/{two}'
                ], 'Server1')
                ->globalConfig(
                    TemplateParam::init('one', 'v1')->dontEncode(),
                    TemplateParam::init('two', 'v2')->dontEncode(),
                    HeaderParam::init('additionalHead1', 'headVal1'),
                    HeaderParam::init('additionalHead2', 'headVal2')
                )
                ->authManagers([
                    "header" => new HeaderAuthManager('someAuthToken', 'accessToken'),
                    "headerWithNull" => new HeaderAuthManager('someAuthToken', null),
                    "query" => new QueryAuthManager('someAuthToken', 'accessToken'),
                    "queryWithNull" => new QueryAuthManager(null, 'accessToken'),
                    "form" => new FormAuthManager('someAuthToken', 'accessToken'),
                    "formWithNull" => new FormAuthManager('newAuthToken', null)
                ])
                ->userAgent("{language}|{version}|{engine}|{engine-version}|{os-info}")
                ->userAgentConfig([
                    '{language}' => 'my lang',
                    '{version}' => '1.*.*'
                ])
                ->inheritedModels([
                    MockClass::class => [
                        MockChild1::class,
                        MockChild2::class
                    ]
                ])
                ->additionalPropertiesMethodName('addAdditionalProperty')
                ->modelNamespace('CoreLib\\Tests\\Mocking\\Other');
            self::$coreConfig = $coreConfigBuilder->build();
            // @phan-suppress-next-next-line PhanPluginDuplicateAdjacentStatement Following duplicated line will
            // call `addUserAgentToGlobalHeaders` again to see test if its added again or not
            self::$coreConfig = $coreConfigBuilder->build();
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
