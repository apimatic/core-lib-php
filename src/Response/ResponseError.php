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

    /**
     * Adds an error to the errors array with the errorCode and ErrorType provided.
     */
    public function addError(string $errorCode, ErrorType $error): void
    {
        $this->errors[$errorCode] = $error;
    }

    /**
     * Sets the flag for throwing exception.
     */
    public function throwException(bool $shouldThrow): void
    {
        $this->throwException = $shouldThrow;
    }

    /**
     * Throws an exception if throwException flag is set and response status code is not within 200-208 range.
     */
    public function throw(Context $context)
    {
        if (!$this->throwException) {
            return;
        }
        $statusCode = $context->getResponse()->getStatusCode();
        if ($context->isSuccess()) {
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
