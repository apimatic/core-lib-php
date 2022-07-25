<?php

declare(strict_types=1);

namespace CoreLib\Core\Response;

use CoreDesign\Core\CoreExceptionInterface;
use CoreDesign\Core\Request\RequestInterface;
use CoreDesign\Core\Response\ResponseInterface;
use CoreDesign\Sdk\ConverterInterface;

class CoreException extends \Exception implements CoreExceptionInterface
{
    private $request;
    private $response;
    public function __construct(string $message, RequestInterface $request, ?ResponseInterface $response)
    {
        $this->request = $request;
        $this->response = $response;
        parent::__construct($message, is_null($response) ? 0 : $response->getStatusCode());
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function convert(ConverterInterface $converter)
    {
        return $converter->createApiException($this);
    }
}
