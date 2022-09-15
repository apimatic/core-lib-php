<?php

declare(strict_types=1);

namespace CoreLib\Core\Response;

use CoreDesign\Core\ContextInterface;
use CoreDesign\Core\Request\RequestInterface;
use CoreDesign\Core\Response\ResponseInterface;
use CoreLib\Core\CoreClient;
use CoreLib\Utils\JsonHelper;

class Context implements ContextInterface
{
    private $request;
    private $response;
    private $converter;
    private $jsonHelper;

    public function __construct(RequestInterface $request, ResponseInterface $response, CoreClient $client)
    {
        $this->request = $request;
        $this->response = $response;
        $this->converter = CoreClient::getConverter($client);
        $this->jsonHelper = CoreClient::getJsonHelper($client);
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
