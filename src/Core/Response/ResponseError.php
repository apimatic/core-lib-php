<?php

namespace CoreLib\Core\Response;

class ResponseError
{
    private $errors;

    /**
     * @var bool
     */
    private $throwException = true;

    /**
     * @param $errors array<int,ErrorType>
     */
    public function __construct(array $errors = [])
    {
        $this->errors = $errors;
    }

    public function addError(int $errorCode, ErrorType $error): void
    {
        $this->errors[$errorCode] = $error;
    }

    public function throwException(bool $shouldThrow): void
    {
        $this->throwException = $shouldThrow;
    }

    public function mergeFrom(self $error): self
    {
        $this->errors = array_merge($this->errors, $error->errors);
        $this->throwException = $error->throwException;
        return $this;
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
        $error = $this->errors[$response->getStatusCode()];
        if (isset($error)) {
            $error->throw($context);
        }
        throw $context->getCoreConfig()->getConverter()->createApiException(
            'Invalid Response.',
            $context->getRequest(),
            $response
        );
    }
}
