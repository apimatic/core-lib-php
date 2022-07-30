<?php

declare(strict_types=1);

namespace CoreLib\Core\Response;

use CoreDesign\Core\ContextInterface;
use CoreDesign\Core\Request\RequestInterface;
use CoreDesign\Core\Response\ResponseInterface;
use CoreLib\Core\CoreConfig;

class Context implements ContextInterface
{
    private $request;
    private $response;
    private $coreConfig;

    public function __construct(RequestInterface $request, ResponseInterface $response, CoreConfig $config)
    {
        $this->request = $request;
        $this->response = $response;
        $this->coreConfig = $config;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getCoreConfig(): CoreConfig
    {
        return $this->coreConfig;
    }

    public function throwErrorFrom(ResponseError $error)
    {
        $this->coreConfig->getGlobalError()
            ->mergeFrom($error)
            ->throw($this);
    }

    public function convertIntoApiResponse($deserializedBody)
    {
        return $this->coreConfig->getConverter()->createApiResponse($this, $deserializedBody);
    }

    public function convert()
    {
        return $this->coreConfig->getConverter()->createHttpContext($this);
    }
}
