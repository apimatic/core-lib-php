<?php

declare(strict_types=1);

namespace CoreLib\Types\Sdk;

use CoreDesign\Sdk\ExceptionInterface;

abstract class CoreApiException extends \Exception implements ExceptionInterface
{
    protected $request;
    protected $response;

    /**
     * @param string $reason the reason for raising an exception
     * @param mixed $request
     * @param mixed $response
     */
    public function __construct(string $reason, $request, $response = null)
    {
        parent::__construct($reason, \is_null($response) ? 0 : $response->getStatusCode());
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Is the response available?
     */
    public function hasResponse(): bool
    {
        return !\is_null($this->response);
    }

    /**
     * Returns the HTTP request
     */
    abstract public function getHttpRequest();

    /**
     * Returns the HTTP response
     */
    abstract public function getHttpResponse();
}
