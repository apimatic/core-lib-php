<?php

namespace CoreLib\Core\Response;

use CoreLib\Core\Response\Types\ErrorType;

class ResponseError
{
    /**
     * @var array<string,ErrorType>
     */
    private $errors;

    /**
     * @var bool
     */
    private $throwException = true;

    public function addError(string $errorCode, ErrorType $error): void
    {
        $this->errors[$errorCode] = $error;
    }

    public function throwException(bool $shouldThrow): void
    {
        $this->throwException = $shouldThrow;
    }

    public function throw(Context $context)
    {
        if (!$this->throwException) {
            return;
        }
        $response = $context->getResponse();
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            return;
        }
        $statusCode = strval($response->getStatusCode());
        if (isset($this->errors[$statusCode])) {
            $this->errors[$statusCode]->throw($context);
        }
        throw $context->toApiException('Invalid Response.');
    }
}
