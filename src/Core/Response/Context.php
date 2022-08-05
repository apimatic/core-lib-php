<?php

declare(strict_types=1);

namespace CoreLib\Core\Response;

use CoreDesign\Core\ContextInterface;
use CoreDesign\Core\Request\RequestInterface;
use CoreDesign\Core\Response\ResponseInterface;
use CoreLib\Core\CoreClient;

class Context implements ContextInterface
{
    private $request;
    private $response;
    private $coreClient;

    public function __construct(RequestInterface $request, ResponseInterface $response, CoreClient $client)
    {
        $this->request = $request;
        $this->response = $response;
        $this->coreClient = $client;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getCoreClient(): CoreClient
    {
        return $this->coreClient;
    }

    public function convertIntoApiResponse($deserializedBody)
    {
        return CoreClient::getConverter($this->coreClient)->createApiResponse($this, $deserializedBody);
    }

    public function convert()
    {
        return CoreClient::getConverter($this->coreClient)->createHttpContext($this);
    }
}
