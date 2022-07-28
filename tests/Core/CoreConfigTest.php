<?php

namespace CoreLib\Tests\Core;

use CoreDesign\Core\Response\ResponseInterface;
use CoreDesign\Http\HttpClientInterface;
use CoreLib\Core\Request\Request;
use CoreLib\Tests\Mocking\MockHelper;
use PHPUnit\Framework\TestCase;

class CoreConfigTest extends TestCase
{
    public function testHttpClient()
    {
        $httpClient = MockHelper::getCoreConfig()->getHttpClient();
        $this->assertInstanceOf(HttpClientInterface::class, $httpClient);

        $request = new Request('some/path');
        $response = $httpClient->execute($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertEquals('{"res":"This is raw body"}', $response->getRawBody());
        $this->assertEquals(["res" => "This is raw body"], $response->getBody());
    }
}
