<?php

namespace CoreLib\Tests\Mocking\Core;

use CoreDesign\Core\Format;
use CoreDesign\Core\Request\ParamInterface;
use CoreDesign\Core\Request\RequestMethod;
use CoreDesign\Http\RetryOption;
use CoreLib\Core\Request\RequestBuilder;
use CoreLib\Core\Response\ResponseHandler;
use CoreLib\Tests\Mocking\MockHelper;
use CoreLib\Tests\Mocking\Other\MockClass;

class MockApiCalls
{
    public static function sendRequestWithOtherConfig()
    {
        return MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::PUT, '/2ndServer')
                ->server('Server2')
                ->auth('header')
                ->retryOption(RetryOption::ENABLE_RETRY))
            ->responseHandler(ResponseHandler::init(Format::JSON)
                ->type(MockClass::class))
            ->execute();
    }

    public static function sendRequestWithParams(ParamInterface ...$parameters)
    {
        return MockHelper::newApiCall()
            ->requestBuilder(RequestBuilder::init(RequestMethod::POST, '/simple/{tyu}')
                ->parameters(...$parameters))
            ->responseHandler(ResponseHandler::init(Format::JSON)
                ->type(MockClass::class))
            ->execute();
    }

    public static function sendRequestWithBodyParams(RequestBuilder $requestBuilder)
    {
        return MockHelper::newApiCall()
            ->requestBuilder($requestBuilder)
            ->responseHandler(ResponseHandler::init(Format::JSON)
                ->type(MockClass::class))
            ->execute();
    }
}
