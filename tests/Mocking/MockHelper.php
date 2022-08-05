<?php

namespace CoreLib\Tests\Mocking;

use CoreLib\Core\ApiCall;
use CoreLib\Core\CoreClient;
use CoreLib\Core\CoreClientBuilder;
use CoreLib\Core\Request\Parameters\HeaderParam;
use CoreLib\Core\Request\Parameters\TemplateParam;
use CoreLib\Core\Response\ResponseHandler;
use CoreLib\Core\Response\Types\ErrorType;
use CoreLib\Tests\Mocking\Authentication\FormAuthManager;
use CoreLib\Tests\Mocking\Authentication\HeaderAuthManager;
use CoreLib\Tests\Mocking\Authentication\QueryAuthManager;
use CoreLib\Tests\Mocking\Core\MockConverter;
use CoreLib\Tests\Mocking\Core\MockHttpClient;
use CoreLib\Tests\Mocking\Core\Response\MockResponse;
use CoreLib\Tests\Mocking\Other\MockChild1;
use CoreLib\Tests\Mocking\Other\MockChild2;
use CoreLib\Tests\Mocking\Other\MockClass;
use CoreLib\Tests\Mocking\Other\MockException1;
use CoreLib\Tests\Mocking\Other\MockException2;
use CoreLib\Tests\Mocking\Types\MockCallback;
use CoreLib\Tests\Mocking\Types\MockFileWrapper;
use CoreLib\Types\CallbackCatcher;

class MockHelper
{
    /**
     * @var CoreClient
     */
    private static $coreClient;

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

    /**
     * @var MockFileWrapper
     */
    private static $urlFileWrapper;

    public static function getCoreClient(): CoreClient
    {
        if (!isset(self::$coreClient)) {
            $coreClientBuilder = CoreClientBuilder::init(new MockHttpClient())
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
                ->globalErrors([
                    400 => ErrorType::init('Exception num 1', MockException1::class),
                    401 => ErrorType::init('Exception num 2', MockException2::class),
                    403 => ErrorType::init('Exception num 3')
                ])
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
            self::$coreClient = $coreClientBuilder->build();
            // @phan-suppress-next-next-line PhanPluginDuplicateAdjacentStatement Following duplicated line will
            // call `addUserAgentToGlobalHeaders` again to see test if its added again or not
            self::$coreClient = $coreClientBuilder->build();
        }
        return self::$coreClient;
    }

    public static function newApiCall(): ApiCall
    {
        return new ApiCall(self::getCoreClient());
    }

    public static function globalResponseHandler(): ResponseHandler
    {
        return self::getCoreClient()->getGlobalResponseHandler();
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

    public static function getFileWrapperFromUrl(): MockFileWrapper
    {
        if (!isset(self::$urlFileWrapper)) {
            $filePath = MockFileWrapper::getDownloadedRealFilePath('https://raw.githubusercontent.com/apimatic/' .
                'core-lib-php/master/tests/Mocking/Other/testFile.txt');
            self::$urlFileWrapper = MockFileWrapper::createFromPath($filePath, 'text/plain', 'My Text');
        }
        return self::$urlFileWrapper;
    }
}
