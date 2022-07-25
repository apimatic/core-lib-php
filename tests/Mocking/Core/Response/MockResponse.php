<?php

namespace CoreLib\Tests\Mocking\Core\Response;

use CoreDesign\Core\Response\ResponseInterface;
use CoreDesign\Sdk\ConverterInterface;

class MockResponse implements ResponseInterface
{
    public function getStatusCode(): int
    {
        return 200;
    }

    public function getHeaders(): array
    {
        return [];
    }

    public function getRawBody(): string
    {
        return '{"res":"This is raw body"}';
    }

    public function getBody()
    {
        return ["res" => "This is raw body"];
    }

    public function convert(ConverterInterface $converter)
    {
        return $converter->createHttpResponse($this);
    }
}
