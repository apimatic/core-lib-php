<?php

declare(strict_types=1);

namespace CoreLib\Core;

use CoreLib\Core\Request\RequestBuilder;
use CoreLib\Core\Response\Context;
use CoreLib\Core\Response\ResponseHandler;

class ApiCall
{
    private $coreConfig;

    /**
     * @var RequestBuilder|null
     */
    private $requestBuilder;

    /**
     * @var ResponseHandler
     */
    private $responseHandler;

    public function __construct(CoreConfig $coreConfig)
    {
        $this->coreConfig = $coreConfig;
        $this->responseHandler = ResponseHandler::init();
    }

    public function requestBuilder(RequestBuilder $requestBuilder): self
    {
        $this->requestBuilder = $requestBuilder;
        return $this;
    }

    public function responseHandler(ResponseHandler $responseHandler): self
    {
        $this->responseHandler = $responseHandler;
        return $this;
    }

    public function execute()
    {
        $request = $this->requestBuilder->build($this->coreConfig);
        $request->addAcceptHeader($this->responseHandler->getFormat());
        $this->coreConfig->beforeRequest($request);
        $response = $this->coreConfig->getHttpClient()->execute($request);
        $context = new Context($request, $response, $this->coreConfig);
        $this->coreConfig->afterResponse($context);
        return $this->responseHandler->getResponse($context);
    }
}
