<?php

declare(strict_types=1);

namespace Core\Response;

use Core\Client;
use Core\Utils\JsonHelper;
use CoreInterfaces\Core\ContextInterface;
use CoreInterfaces\Core\Request\RequestInterface;
use CoreInterfaces\Core\Response\ResponseInterface;

class Context implements ContextInterface
{
    private $request;
    private $response;
    private $converter;
    private $jsonHelper;

    public function __construct(RequestInterface $request, ResponseInterface $response, Client $client)
    {
        $this->request = $request;
        $this->response = $response;
        $this->converter = Client::getConverter($client);
        $this->jsonHelper = Client::getJsonHelper($client);
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getJsonHelper(): JsonHelper
    {
        return $this->jsonHelper;
    }

    public function toApiException(string $errorMessage, ?string $childClass = null)
    {
        $responseBody = $this->response->getBody();
        if (is_null($childClass) || !is_object($responseBody)) {
            return $this->converter->createApiException($errorMessage, $this->request, $this->response);
        }
        $responseBody->reason = $errorMessage;
        $responseBody->request = $this->request->convert();
        $responseBody->response = $this->response->convert($this->converter);
        return $this->jsonHelper->mapClass($responseBody, $childClass);
    }

    public function toApiResponse($deserializedBody)
    {
        return $this->converter->createApiResponse($this, $deserializedBody);
    }
}
