<?php

namespace CoreLib\Tests\Mocking\Core;

use CoreDesign\Core\Request\RequestInterface;
use CoreDesign\Core\Response\ResponseInterface;
use CoreDesign\Http\HttpClientInterface;
use CoreLib\Tests\Mocking\Core\Response\MockResponse;

class MockHttpClient implements HttpClientInterface
{
    public function execute(RequestInterface $request): ResponseInterface
    {
        return new MockResponse($request);
    }
}
