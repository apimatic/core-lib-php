<?php

namespace CoreLib\Tests\Core;

use CoreDesign\Core\Request\RequestMethod;
use CoreLib\Core\Request\Request;
use CoreLib\Core\Response\Context;
use CoreLib\Tests\Mocking\MockHelper;
use CoreLib\Tests\Mocking\Other\MockClass;
use CoreLib\Tests\Mocking\Types\MockApiResponse;
use CoreLib\Tests\Mocking\Types\MockContext;
use CoreLib\Tests\Mocking\Types\MockRequest;
use CoreLib\Tests\Mocking\Types\MockResponse;
use PHPUnit\Framework\TestCase;

class TypesTest extends TestCase
{
    public function testChildOfCoreRequest()
    {
        $request = new Request('some/path');
        $sdkRequest = $request->convert(MockHelper::getCoreConfig()->getConverter());

        $this->assertInstanceOf(MockRequest::class, $sdkRequest);
        $sdkRequest->setHttpMethod(RequestMethod::POST);
        $this->assertEquals(RequestMethod::POST, $sdkRequest->getHttpMethod());
        $sdkRequest->setQueryUrl('some/new/path');
        $this->assertEquals('some/new/path', $sdkRequest->getQueryUrl());
        $sdkRequest->setHeaders(['def' => 'def value']);
        $sdkRequest->addHeader('def2', 'def2 value');
        $this->assertEquals(['def' => 'def value', 'def2' => 'def2 value'], $sdkRequest->getHeaders());
        $sdkRequest->setParameters(['def' => 'def value']);
        $this->assertEquals(['def' => 'def value'], $sdkRequest->getParameters());
    }

    public function testChildOfCoreResponse()
    {
        $response = MockHelper::getResponse();
        $sdkResponse = $response->convert(MockHelper::getCoreConfig()->getConverter());

        $this->assertInstanceOf(MockResponse::class, $sdkResponse);
        $this->assertEquals(200, $sdkResponse->getStatusCode());
        $this->assertEquals([], $sdkResponse->getHeaders());
        $this->assertEquals('{"res":"This is raw body"}', $sdkResponse->getRawBody());
    }

    public function testChildOfCoreContext()
    {
        $request = new Request('some/path');
        $response = MockHelper::getResponse();
        $context = new Context($request, $response, MockHelper::getCoreConfig());
        $sdkContext = $context->convert();

        $this->assertInstanceOf(MockContext::class, $sdkContext);
        $this->assertInstanceOf(MockRequest::class, $sdkContext->getRequest());
        $this->assertInstanceOf(MockResponse::class, $sdkContext->getResponse());
    }

    public function testChildOfCoreApiResponse()
    {
        $request = new Request('some/path');
        $response = MockHelper::getResponse();
        $context = new Context($request, $response, MockHelper::getCoreConfig());
        $sdkApiResponse = $context->convertIntoApiResponse(["alpha", "beta"]);

        $this->assertInstanceOf(MockApiResponse::class, $sdkApiResponse);
        $this->assertInstanceOf(MockRequest::class, $sdkApiResponse->getRequest());
        $this->assertEquals([], $sdkApiResponse->getHeaders());
        $this->assertEquals(200, $sdkApiResponse->getStatusCode());
        $this->assertEquals('{"res":"This is raw body"}', $sdkApiResponse->getBody());
        $this->assertNull($sdkApiResponse->getReasonPhrase());
        $this->assertEquals(["alpha", "beta"], $sdkApiResponse->getResult());
    }
    public function testCoreExceptionConverter()
    {
        $request = new Request('some/path');
        $response = MockHelper::getResponse();
        $sdkException = MockHelper::getCoreConfig()
            ->getConverter()
            ->createApiException('Error Occurred', $request, $response);

        $this->assertInstanceOf(MockClass::class, $sdkException);
        $this->assertEquals('Error Occurred', $sdkException->body[0]);
        $this->assertInstanceOf(MockRequest::class, $sdkException->body[1]);
        $this->assertInstanceOf(MockResponse::class, $sdkException->body[2]);
    }

    public function testCallbackCatcher()
    {
        $callback = MockHelper::getCallbackCatcher();
        $request = new Request('some/path');
        $this->assertNull($callback->getOnBeforeRequest());
        $callback->callOnBeforeWithConversion($request, MockHelper::getCoreConfig()->getConverter());

        $response = MockHelper::getResponse();
        $context = new Context($request, $response, MockHelper::getCoreConfig());
        $callback->callOnAfterWithConversion($context, MockHelper::getCoreConfig()->getConverter());

        $this->assertEquals($request, $callback->getRequest());
        $this->assertEquals($response, $callback->getResponse());
    }

    public function testChildOfCoreCallback()
    {
        $callback = MockHelper::getCallback();
        $callback->setOnBeforeRequest(function (MockRequest $sdkRequest): void {
            $this->assertInstanceOf(MockRequest::class, $sdkRequest);
            $this->assertEquals(RequestMethod::GET, $sdkRequest->getHttpMethod());
            $this->assertEquals('some/path', $sdkRequest->getQueryUrl());
            $this->assertEquals([], $sdkRequest->getHeaders());
            $this->assertEquals([], $sdkRequest->getParameters());
        });
        $callback->setOnAfterRequest(function (MockContext $sdkContext): void {
            $this->assertInstanceOf(MockRequest::class, $sdkContext->getRequest());
            $this->assertInstanceOf(MockResponse::class, $sdkContext->getResponse());
        });
        $this->assertNotNull($callback->getOnBeforeRequest());
        $this->assertNotNull($callback->getOnAfterRequest());

        $request = new Request('some/path');
        $response = MockHelper::getResponse();
        $context = new Context($request, $response, MockHelper::getCoreConfig());

        $callback->callOnBeforeWithConversion($request, MockHelper::getCoreConfig()->getConverter());
        $callback->callOnAfterWithConversion($context, MockHelper::getCoreConfig()->getConverter());
    }

    public function testChildOfCoreFileWrapper()
    {
        $fileWrapper = MockHelper::getFileWrapper();

        $this->assertEquals('text/plain', $fileWrapper->getMimeType());
        $this->assertEquals('My Text', $fileWrapper->getFilename());
        $this->assertEquals(
            'This test file is created to test CoreFileWrapper functionality',
            $fileWrapper->getContent()
        );
        $curlFile = $fileWrapper->createCurlFileInstance('application/octet-stream');
        $this->assertStringEndsWith('testFile.txt', $curlFile->getFilename());
        $this->assertEquals('text/plain', $curlFile->getMimeType());
        $this->assertEquals('My Text', $curlFile->getPostFilename());
    }
}
