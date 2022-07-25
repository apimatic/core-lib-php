<?php

namespace CoreLib\Tests\Mocking\Core;

use CoreDesign\Core\Request\RequestInterface;
use CoreDesign\Core\Response\ResponseInterface;
use CoreDesign\Http\HttpClientInterface;
use CoreLib\Tests\Mocking\MockHelper;

class MockHttpClient implements HttpClientInterface
{
    public function execute(RequestInterface $request): ResponseInterface
    {
        return MockHelper::getResponse();
    }
}
