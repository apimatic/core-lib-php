<?php

namespace CoreLib\Core\Response;

class ResponseError
{
    /**
     * @var array<int, string[]>
     */
    private $errors = [];

    /**
     * @var bool
     */
    private $throwApiException = true;

    public function addError(int $errorCode, string $errorClass, string $errorDescription): void
    {
        $this->errors[$errorCode] = [$errorClass, $errorDescription];
    }

    public function throwApiException(bool $throwApiException): void
    {
        $this->throwApiException = $throwApiException;
    }

    public function throw(Context $context)
    {
        if (!$this->throwApiException) {
            return;
        }
        $response = $context->getResponse();
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            return;
        }
        $body = $response->getBody();
        $error = $this->errors[$response->getStatusCode()];
        if (isset($error, $body)) {
            $body->reason = $error[1];
            $body->request = $context->getRequest();
            $body->response = $response;
            throw $context->getCoreConfig()->getJsonHelper()->mapClass($body, $error[0]);
        }
        throw $context->getCoreConfig()->getConverter()->createApiException(
            'Invalid Response.',
            $context->getRequest(),
            $response
        );
    }
}
