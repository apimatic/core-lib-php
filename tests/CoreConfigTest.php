<?php

namespace CoreLib\Tests;

use CoreDesign\Core\ContextInterface;
use CoreDesign\Core\Request\RequestInterface;
use CoreDesign\Core\Request\RequestMethod;
use CoreDesign\Core\Response\ResponseInterface;
use CoreDesign\Http\HttpClientInterface;
use CoreDesign\Http\RetryOption;
use CoreLib\Core\Request\Request;
use CoreLib\Core\Response\Context;
use CoreLib\Core\Response\CoreException;
use CoreLib\Tests\Mocking\MockHelper;
use CoreLib\Tests\Mocking\Other\MockClass;
use PHPUnit\Framework\TestCase;

class CoreConfigTest extends TestCase
{
    public function testHttpClient()
    {
        $httpClient = MockHelper::getCoreConfig()->getHttpClient();
        $this->assertInstanceOf(HttpClientInterface::class, $httpClient);

        $request = new Request(RequestMethod::GET, 'some/path');
        $response = $httpClient->execute($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertEquals('{"res":"This is raw body"}', $response->getRawBody());
        $this->assertEquals(["res" => "This is raw body"], $response->getBody());
    }

    public function testConverter()
    {
        $converter = MockHelper::getCoreConfig()->getConverter();
        $request = new Request(RequestMethod::GET, 'some/path');
        $response = MockHelper::getResponse();
        $context = new Context($request, $response);
        $exception = new CoreException('Error Occurred', $request, $response);

        $mockVal = $request->convert($converter);
        $this->assertInstanceOf(MockClass::class, $mockVal);
        $this->assertEquals(RequestMethod::GET, $mockVal->body[0]);
        $this->assertEquals('some/path', $mockVal->body[1]);
        $this->assertEquals([], $mockVal->body[2]);
        $this->assertEquals([], $mockVal->body[3]);
        $this->assertEquals(null, $mockVal->body[4]);
        $this->assertEquals(RetryOption::USE_GLOBAL_SETTINGS, $mockVal->body[5]);

        $mockVal = $response->convert($converter);
        $this->assertInstanceOf(MockClass::class, $mockVal);
        $this->assertEquals(200, $mockVal->body[0]);
        $this->assertEquals([], $mockVal->body[1]);
        $this->assertEquals('{"res":"This is raw body"}', $mockVal->body[2]);
        $this->assertEquals(["res" => "This is raw body"], $mockVal->body[3]);

        $mockVal = $context->convert($converter);
        $this->assertInstanceOf(MockClass::class, $mockVal);
        $this->assertInstanceOf(RequestInterface::class, $mockVal->body[0]);
        $this->assertInstanceOf(ResponseInterface::class, $mockVal->body[1]);

        $mockVal = $exception->convert($converter);
        $this->assertInstanceOf(MockClass::class, $mockVal);
        $this->assertInstanceOf(RequestInterface::class, $mockVal->body[0]);
        $this->assertInstanceOf(ResponseInterface::class, $mockVal->body[1]);

        $mockVal = $context->convertIntoApiResponse(["alpha", "beta"], $converter);
        $this->assertInstanceOf(MockClass::class, $mockVal);
        $this->assertInstanceOf(ContextInterface::class, $mockVal->body[0]);
        $this->assertEquals(["alpha", "beta"], $mockVal->body[1]);
    }
}
