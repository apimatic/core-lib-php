<?php

declare(strict_types=1);

namespace CoreLib\Core\Response;

use CoreDesign\Core\Format;
use CoreLib\Core\Response\Types\DeserializableType;
use CoreLib\Core\Response\Types\ErrorType;
use CoreLib\Core\Response\Types\ResponseMultiType;
use CoreLib\Core\Response\Types\ResponseType;
use CoreLib\Utils\XmlDeserializer;

class ResponseHandler
{
    private $format = Format::SCALAR;
    private $deserializableType;
    private $responseType;
    private $responseMultiType;
    private $responseError;
    private $useApiResponse = false;
    private $nullOn404 = false;

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

    public function nullOn404(): self
    {
        $this->nullOn404 = true;
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
    public function getResult(Context $context)
    {
        if ($this->nullOn404 && $context->getResponse()->getStatusCode() === 404) {
            return null;
        }
        $this->responseError->throw($context);
        $result = $this->deserializableType->getFrom($context);
        $result = $result ?? $this->responseType->getFrom($context);
        $result = $result ?? $this->responseMultiType->getFrom($context);
        if (is_null($result)) {
            $responseBody = $context->getResponse()->getBody();
            $result = is_object($responseBody) ? (array) $responseBody : $responseBody;
        }
        if ($this->useApiResponse) {
            return $context->toApiResponse($result);
        }
        return $result;
    }
}
