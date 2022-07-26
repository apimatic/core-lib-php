<?php

namespace CoreLib\Tests\Core;

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
use CoreLib\Tests\Mocking\Types\MockApiResponse;
use CoreLib\Tests\Mocking\Types\MockContext;
use CoreLib\Tests\Mocking\Types\MockRequest;
use CoreLib\Tests\Mocking\Types\MockResponse;
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
}
