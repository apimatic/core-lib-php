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
    private $useApiResponse = false;
    private $mapErrorTypes = false;
    private $nullOn404 = false;

    /**
     * Adds an error to the errors array with the errorCode and ErrorType provided.
     */
    public function addError(string $errorCode, ErrorType $error): void
    {
        $this->errors[$errorCode] = $error;
    }

    /**
     * Sets the useApiResponse flag.
     */
    public function returnApiResponse(): void
    {
        $this->useApiResponse = true;
    }

    /**
     * Sets the mapErrorTypes flag.
     */
    public function mapErrorTypesInApiResponse()
    {
        $this->mapErrorTypes = true;
    }

    /**
     * Sets the nullOn404 flag.
     */
    public function nullOn404(): void
    {
        $this->nullOn404 = true;
    }

    /**
     * Returns calculated result on failure or throws an exception.
     */
    public function getResult(Context $context)
    {
        if ($this->useApiResponse) {
            return $this->getApiResponse($context);
        }
        $statusCode = $context->getResponse()->getStatusCode();
        if ($this->shouldReturnNull($statusCode)) {
            return null;
        }
        if (isset($this->errors[strval($statusCode)])) {
            throw $this->errors[strval($statusCode)]->throwable($context);
        }
        if (isset($this->errors[strval(0)])) {
            throw $this->errors[strval(0)]->throwable($context); // throw default error (if set)
        }
        throw $context->toApiException('HTTP Response Not OK');
    }

    private function getApiResponse(Context $context)
    {
        $statusCode = $context->getResponse()->getStatusCode();
        if ($this->shouldReturnNull($statusCode)) {
            return $context->toApiResponse(null);
        }
        if (!$this->mapErrorTypes) {
            return $context->toApiResponse($context->getResponseBody());
        }

        $errorTypeName = null;
        if (isset($this->errors[strval($statusCode)])) {
            $errorTypeName = $this->errors[strval($statusCode)]->getClassName();
        }
        if (isset($this->errors[strval(0)])) {
            $errorTypeName = $this->errors[strval(0)]->getClassName();
        }
        return $context->toApiResponseWithMappedType($errorTypeName);
    }

    private function shouldReturnNull(int $statusCode): bool
    {
        if (!$this->nullOn404) {
            return false;
        }
        return $statusCode == 404;
    }
}
