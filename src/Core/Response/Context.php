<?php

declare(strict_types=1);

namespace CoreLib\Core\Response;

use CoreDesign\Core\ContextInterface;
use CoreDesign\Core\Request\RequestInterface;
use CoreDesign\Core\Response\ResponseInterface;
use CoreDesign\Sdk\ConverterInterface;

class Context implements ContextInterface
{
    private $request;
    private $response;

    public function __construct(RequestInterface $request, ResponseInterface $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function convertIntoApiResponse($deserializedBody, ConverterInterface $converter)
    {
        return $converter->createApiResponse($this, $deserializedBody);
    }

    public function convert(ConverterInterface $converter)
    {
        return $converter->createHttpContext($this);
    }
}
