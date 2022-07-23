<?php

namespace CoreLib\Tests\Core;

use CoreDesign\Core\ContextInterface;
use CoreDesign\Core\Request\RequestInterface;
use CoreDesign\Core\Request\RequestMethod;
use CoreDesign\Core\Response\ResponseInterface;
use CoreDesign\Http\HttpClientInterface;
use CoreLib\Tests\Mocking\Core\Response\MockResponse;
use CoreLib\Tests\Mocking\MockClass;
use CoreLib\Tests\TestHelper;
use CoreLib\Types\Request\Request;
use CoreLib\Types\Response\Context;
use CoreLib\Types\Response\CoreException;
use PHPUnit\Framework\TestCase;

class CoreConfigTest extends TestCase
{
    public function testAllHttpConfigs()
    {
        $httpConfigurations = TestHelper::getMockCoreConfig()->getHttpConfigurations();
        $this->assertEquals(20, $httpConfigurations->getTimeout());
        $this->assertEquals(2.0, $httpConfigurations->getBackOffFactor());
        $this->assertEquals([RequestMethod::GET, RequestMethod::PUT], $httpConfigurations->getHttpMethodsToRetry());
        $this->assertEquals([500, 501], $httpConfigurations->getHttpStatusCodesToRetry());
        $this->assertEquals(120, $httpConfigurations->getMaximumRetryWaitTime());
        $this->assertEquals(2, $httpConfigurations->getNumberOfRetries());
        $this->assertEquals(2.0, $httpConfigurations->getRetryInterval());
        $this->assertEquals(true, $httpConfigurations->shouldEnableRetries());
        $this->assertEquals(true, $httpConfigurations->shouldRetryOnTimeout());
    }

    public function testHttpClient()
    {
        $httpClient = TestHelper::getMockCoreConfig()->getHttpClient();
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
        $converter = TestHelper::getMockCoreConfig()->getConverter();
        $request = new Request(RequestMethod::GET, 'some/path');
        $response = new MockResponse();
        $context = new Context($request, $response);
        $exception = new CoreException('Error Occurred', $request, $response);

        $mockVal = $converter->createHttpRequest($request);
        $this->assertInstanceOf(MockClass::class, $mockVal);
        $this->assertEquals(RequestMethod::GET, $mockVal->body[0]);
        $this->assertEquals('some/path', $mockVal->body[1]);
        $this->assertEquals([], $mockVal->body[2]);
        $this->assertEquals([], $mockVal->body[3]);
        $this->assertEquals(null, $mockVal->body[4]);

        $mockVal = $converter->createHttpResponse($response);
        $this->assertInstanceOf(MockClass::class, $mockVal);
        $this->assertEquals(200, $mockVal->body[0]);
        $this->assertEquals([], $mockVal->body[1]);
        $this->assertEquals('{"res":"This is raw body"}', $mockVal->body[2]);
        $this->assertEquals(["res" => "This is raw body"], $mockVal->body[3]);

        $mockVal = $converter->createHttpContext($context);
        $this->assertInstanceOf(MockClass::class, $mockVal);
        $this->assertInstanceOf(RequestInterface::class, $mockVal->body[0]);
        $this->assertInstanceOf(ResponseInterface::class, $mockVal->body[1]);

        $mockVal = $converter->createApiException($exception);
        $this->assertInstanceOf(MockClass::class, $mockVal);
        $this->assertInstanceOf(RequestInterface::class, $mockVal->body[0]);
        $this->assertInstanceOf(ResponseInterface::class, $mockVal->body[1]);

        $mockVal = $converter->createApiResponse($context, ["alpha", "beta"]);
        $this->assertInstanceOf(MockClass::class, $mockVal);
        $this->assertInstanceOf(ContextInterface::class, $mockVal->body[0]);
        $this->assertEquals(["alpha", "beta"], $mockVal->body[1]);
    }
}
