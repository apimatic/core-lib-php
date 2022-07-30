<?php

namespace CoreLib\Tests\Mocking\Core\Response;

use CoreDesign\Core\Request\RequestInterface;
use CoreDesign\Core\Response\ResponseInterface;
use CoreDesign\Sdk\ConverterInterface;
use CoreLib\Tests\Mocking\Other\MockClass;
use CoreLib\Utils\CoreHelper;

class MockResponse implements ResponseInterface
{
    private $body;
    private $rawBody;
    public function __construct(?RequestInterface $request = null)
    {
        if (is_null($request)) {
            return;
        }
        $this->body = (object)(array) new MockClass([
            'httpMethod' => $request->getHttpMethod(),
            'queryUrl' => $request->getQueryUrl(),
            'headers' => $request->getHeaders(),
            'parameters' => $request->getParameters(),
            'body' => $request->getBody(),
            'retryOption' => $request->getRetryOption()
        ]);
        $this->rawBody = CoreHelper::serialize($this->body);
    }

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
        return $this->rawBody ?? '{"res":"This is raw body"}';
    }

    public function getBody()
    {
        return $this->body ?? ["res" => "This is raw body"];
    }

    public function convert(ConverterInterface $converter)
    {
        return $converter->createHttpResponse($this);
    }
}
