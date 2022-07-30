<?php

namespace CoreLib\Core\Response;

class ErrorType
{
    public static function init(string $description, ?string $className = null): self
    {
        return new self($description, $className);
    }

    private $description;
    private $className;
    private function __construct(string $description, ?string $className)
    {
        $this->description = $description;
        $this->className = $className;
    }

    public function throw(Context $context)
    {
        $response = $context->getResponse();
        $body = $response->getBody();
        if (isset($this->className, $body)) {
            $body->reason = $this->description;
            $body->request = $context->getRequest();
            $body->response = $response;
            throw $context->getCoreConfig()->getJsonHelper()->mapClass($body, $this->className);
        }
        throw $context->getCoreConfig()->getConverter()->createApiException(
            $this->description,
            $context->getRequest(),
            $response
        );
    }
}
