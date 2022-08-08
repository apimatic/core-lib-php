<?php

namespace CoreLib\Core\Response\Types;

use Closure;
use CoreLib\Core\Response\Context;
use Exception;

class ResponseType
{
    /**
     * @var string|null
     */
    private $responseClass;

    /**
     * @var callable|null
     */
    private $xmlDeserializer;

    /**
     * @var int|null
     */
    private $dimensions;

    public function setResponseClass(string $responseClass): void
    {
        $this->responseClass = $responseClass;
    }

    public function setXmlDeserializer(callable $xmlDeserializer): void
    {
        $this->xmlDeserializer = $xmlDeserializer;
    }

    public function setDimensions(int $dimensions): void
    {
        $this->dimensions = $dimensions;
    }

    public function getFrom(Context $context)
    {
        if (is_null($this->responseClass)) {
            return null;
        }
        try {
            if (isset($this->xmlDeserializer)) {
                return Closure::fromCallable($this->xmlDeserializer)(
                    $context->getResponse()->getRawBody(),
                    $this->responseClass
                );
            }
            return $context->getJsonHelper()->mapClass(
                $context->getResponse()->getBody(),
                $this->responseClass,
                $this->dimensions
            );
        } catch (Exception $e) {
            throw $context->toApiException($e->getMessage());
        }
    }
}
