<?php

declare(strict_types=1);

namespace CoreLib\Core\Response;

use CoreDesign\Core\Format;
use CoreLib\Utils\XmlDeserializer;

class ResponseHandler
{
    private $format = Format::SCALAR;
    private $deserializableType;
    private $responseType;
    private $responseMultiType;
    private $responseError;
    private $useApiResponse = false;

    public function __construct()
    {
        $this->responseError = new ResponseError();
        $this->deserializableType = new DeserializableType();
        $this->responseType = new ResponseType();
        $this->responseMultiType = new ResponseMultiType();
    }

    public function throwErrorOn(int $statusCode, ErrorType $error): self
    {
        $this->responseError->addError(strval($statusCode), $error);
        return $this;
    }

    public function returnApiResponse(): self
    {
        $this->responseError->throwException(false);
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
        $this->format = Format::JSON;
        $this->responseType->setResponseClass($responseClass);
        $this->responseType->setDimensions($dimensions);
        return $this;
    }

    public function typeXml(string $responseClass, string $rootName): self
    {
        $this->format = Format::XML;
        $this->responseType->setResponseClass($responseClass);
        $this->responseType->setXmlDeserializer(function ($value, $class) use ($rootName) {
            return (new XmlDeserializer())->deserialize($value, $rootName, $class);
        });
        return $this;
    }

    public function typeXmlMap(string $responseClass, string $rootName): self
    {
        $this->format = Format::XML;
        $this->responseType->setResponseClass($responseClass);
        $this->responseType->setXmlDeserializer(function ($value, $class) use ($rootName): ?array {
            return (new XmlDeserializer())->deserializeToMap($value, $rootName, $class);
        });
        return $this;
    }

    public function typeXmlArray(string $responseClass, string $rootName, string $itemName): self
    {
        $this->format = Format::XML;
        $this->responseType->setResponseClass($responseClass);
        $this->responseType->setXmlDeserializer(function ($value, $class) use ($rootName, $itemName): ?array {
            return (new XmlDeserializer())->deserializeToArray($value, $rootName, $itemName, $class);
        });
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
        $this->format = Format::JSON;
        $this->responseMultiType->setTypeGroup($typeGroup);
        $this->responseMultiType->setDeserializers($typeGroupDeserializers);
        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @param Context $context
     * @return mixed
     */
    public function getResponse(Context $context)
    {
        $this->responseError->throw($context);
        $response = $this->deserializableType->getFrom($context);
        $response = $response ?? $this->responseType->getFrom($context);
        $response = $response ?? $this->responseMultiType->getFrom($context);
        $response = $response ?? $context->getResponse()->getBody();
        if ($this->useApiResponse) {
            return $context->convertIntoApiResponse($response);
        }
        return $response;
    }
}
