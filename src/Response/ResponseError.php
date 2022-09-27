<?php

declare(strict_types=1);

namespace Core\Response;

use Core\Response\Types\ErrorType;

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
        $statusCode = $context->getResponse()->getStatusCode();
        if ($statusCode == min(max($statusCode, 200), 208)) { // [200,208] = HTTP OK
            return;
        }
        if (isset($this->errors[strval($statusCode)])) {
            throw $this->errors[strval($statusCode)]->throwable($context);
        }
        if (isset($this->errors[strval(0)])) {
            throw $this->errors[strval(0)]->throwable($context); // throw default error (if set)
        }
        throw $context->toApiException('HTTP Response Not OK');
    }
}
