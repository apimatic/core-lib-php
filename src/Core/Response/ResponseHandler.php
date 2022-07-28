<?php

declare(strict_types=1);

namespace CoreLib\Core\Response;

use CoreDesign\Core\Format;

class ResponseHandler
{
    public static function init(string $format = Format::JSON): self
    {
        return new self($format);
    }

    private $format;
    private $deserializableType;
    private $responseType;
    private $responseMultiType;
    private $responseError;
    private $useApiResponse = false;

    private function __construct(string $format)
    {
        $this->format = $format;
        $this->responseError = new ResponseError();
        $this->deserializableType = new DeserializableType();
        $this->responseType = new ResponseType();
        $this->responseMultiType = new ResponseMultiType();
    }

    public function throwErrorOn(int $statusCode, string $exceptionClass, string $description): self
    {
        $this->responseError->addError($statusCode, $exceptionClass, $description);
        return $this;
    }

    public function returnApiResponse(): self
    {
        $this->responseError->throwApiException(false);
        $this->useApiResponse = true;
        return $this;
    }

    public function deserializerMethod(callable $deserializerMethod): self
    {
        $this->deserializableType->setDeserializerMethod($deserializerMethod);
        return $this;
    }

    public function type(string $responseClass, int $dimensions = 0): self
    {
        $this->responseType->setResponseClass($responseClass);
        $this->responseType->setDimensions($dimensions);
        return $this;
    }

    /**
     * @param string $typeGroup                Group of types in string format i.e. oneof(...), anyof(...)
     * @param string[] $typeGroupDeserializers Methods required for deserialization of specific types in
     *                                         in the provided typeGroup, should be an array in the format:
     *                                         ['path/to/method returnType', ...]. Default: []
     * @return $this
     */
    public function typeGroup(string $typeGroup, array $typeGroupDeserializers = []): self
    {
        $this->responseMultiType->setTypeGroup($typeGroup);
        $this->responseMultiType->setDeserializers($typeGroupDeserializers);
        return $this;
    }

    /**
     * @param Context $context
     * @return mixed
     */
    public function getResponse(Context $context)
    {
        $this->responseError->throw($context);
        $response = $this->deserializableType->getFrom($context);
        $response = $response ?? $this->responseType->getFrom($context, $this->format);
        $response = $response ?? $this->responseMultiType->getFrom($context, $this->format);
        $response = $response ?? $context->getResponse()->getBody();
        if ($this->useApiResponse) {
            return $context->convertIntoApiResponse($response);
        }
        return $response;
    }
}
