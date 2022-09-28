<?php

declare(strict_types=1);

namespace Core\Response;

use Core\Response\Types\DeserializableType;
use Core\Response\Types\ErrorType;
use Core\Response\Types\ResponseMultiType;
use Core\Response\Types\ResponseType;
use Core\Utils\XmlDeserializer;
use CoreInterfaces\Core\Format;

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

    /**
     * Associates an ErrorType object to the statusCode provided.
     */
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

    /**
     * Sets the nullOn404 flag.
     */
    public function nullOn404(): self
    {
        $this->nullOn404 = true;
        return $this;
    }

    /**
     * Sets the deserializer method to the one provided, for deserializableType.
     */
    public function deserializerMethod(callable $deserializerMethod): self
    {
        $this->deserializableType->setDeserializerMethod($deserializerMethod);
        return $this;
    }

    /**
     * Sets response type to the one provided and format to JSON.
     *
     * @param string $responseClass Response type class
     * @param int $dimensions Dimensions to be provided in case of an array
     */
    public function type(string $responseClass, int $dimensions = 0): self
    {
        $this->format = Format::JSON;
        $this->responseType->setResponseClass($responseClass);
        $this->responseType->setDimensions($dimensions);
        return $this;
    }

    /**
     * Sets response type to the one provided and format to XML.
     *
     * @param string $responseClass Response type class
     * @param string $rootName
     */
    public function typeXml(string $responseClass, string $rootName): self
    {
        $this->format = Format::XML;
        $this->responseType->setResponseClass($responseClass);
        $this->responseType->setXmlDeserializer(function ($value, $class) use ($rootName) {
            return (new XmlDeserializer())->deserialize($value, $rootName, $class);
        });
        return $this;
    }

    /**
     * Sets response type to the one provided and format to XML.
     */
    public function typeXmlMap(string $responseClass, string $rootName): self
    {
        $this->format = Format::XML;
        $this->responseType->setResponseClass($responseClass);
        $this->responseType->setXmlDeserializer(function ($value, $class) use ($rootName): ?array {
            return (new XmlDeserializer())->deserializeToMap($value, $rootName, $class);
        });
        return $this;
    }

    /**
     * Sets response type to the one provided and format to XML.
     */
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

    /**
     * Returns current set format.
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Returns response from the context provided.
     *
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
