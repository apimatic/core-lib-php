<?php

namespace CoreLib\Tests\Mocking\Core;

use CoreDesign\Core\ContextInterface;
use CoreDesign\Core\CoreExceptionInterface;
use CoreDesign\Core\Request\RequestInterface;
use CoreDesign\Core\Response\ResponseInterface;
use CoreDesign\Sdk\ConverterInterface;
use CoreLib\Tests\Mocking\MockClass;

class MockConverter implements ConverterInterface
{
    public function createApiException(CoreExceptionInterface $exception): MockClass
    {
        return new MockClass($exception->getRequest(), $exception->getResponse());
    }

    public function createHttpContext(ContextInterface $context): MockClass
    {
        return new MockClass($context->getRequest(), $context->getResponse());
    }

    public function createHttpRequest(RequestInterface $request): MockClass
    {
        return new MockClass(
            $request->getHttpMethod(),
            $request->getQueryUrl(),
            $request->getHeaders(),
            $request->getParameters(),
            $request->getBody(),
            $request->getRetryOption()
        );
    }

    public function createHttpResponse(ResponseInterface $response): MockClass
    {
        return new MockClass(
            $response->getStatusCode(),
            $response->getHeaders(),
            $response->getRawBody(),
            $response->getBody()
        );
    }

    public function createApiResponse(ContextInterface $context, $deserializedBody): MockClass
    {
        return new MockClass($context, $deserializedBody);
    }
}
