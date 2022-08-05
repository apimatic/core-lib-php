<?php

namespace CoreLib\Core\Response\Types;

use CoreLib\Core\CoreClient;
use CoreLib\Core\Response\Context;

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
        $converter = CoreClient::getConverter($context->getCoreClient());
        if (isset($this->className, $body)) {
            $body->reason = $this->description;
            $body->request = $converter->createHttpRequest($context->getRequest());
            $body->response = $converter->createHttpResponse($response);
            throw CoreClient::getJsonHelper($context->getCoreClient())->mapClass($body, $this->className);
        }
        throw $converter->createApiException(
            $this->description,
            $context->getRequest(),
            $response
        );
    }
}
